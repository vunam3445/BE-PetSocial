import pandas as pd
from sqlalchemy import create_engine, text
import json
import uuid
import re
from datetime import datetime
from sentence_transformers import SentenceTransformer, util
import torch

# 1. KHỞI TẠO MODEL
print("--- [AI ENGINE] Đang khởi động mô hình ngôn ngữ BERT... ---")
# Model đa ngôn ngữ hỗ trợ tốt Tiếng Việt và Tiếng Anh
model = SentenceTransformer('paraphrase-multilingual-MiniLM-L12-v2')

DB_CONFIG = {
    'user': 'postgres',
    'pass': '123456',
    'host': '127.0.0.1',
    'port': '5432',
    'name': 'PetSocial'
}

# --- GIẢI PHÁP TÁCH TỪ VÀ CHUẨN HÓA HASHTAG ---
def segment_hashtag(tag):
    tag = tag.lower().replace('#', '').strip()
    
    # Danh sách từ khóa cốt lõi phong phú
    core_keywords = [
        'cho', 'meo', 'cun', 'miu', 'husky', 'poodle', 'pug', 'corgi', 'shiba', 'phoc', 
        'thanlan', 'thannlan', 'than', 'lan', 'bo_sat', 'ran', 'rua', 'ky_nhong', 'tac_ke',
        'chim', 'vet', 'khuyen', 'yen_phung', 'ga_canh',
        'ca_canh', 'ca_koi', 'thuy_sinh', 'tep', 'ha_ma',
        'hamster', 'soc', 'tho', 'chuot_lang', 'guinea_pig', 'nhim',
        'chamsoc', 'nuoi', 'day', 'huanluyen', 'tam', 'rua', 'vesinh',
        'cat_tia', 'spa', 'lamdep', 'grooming', 'massage',
        'kham', 'benh', 'y_te', 'thu_y', 'thuy', 'tiem_phong', 'vacxin', 
        'diet_ve', 'tay_giun', 'tri_benh', 'suckhoe',
        'thucan', 'pate', 'hat', 'xuong_gam', 'banh_thuong', 'snack', 'dinhduong',
        'dodung', 'phukien', 'vong_co', 'day_dat', 'ao_quan', 'thoitrang',
        'chuong', 'long', 'nem', 'o_nam', 'tui_van_chuyen', 'balo',
        'do_choi', 'cat_meo', 'khay_ve_sinh', 'bat_an',
        'didao', 'offline', 'clb', 'hoi_nhom', 'cuu_tro', 'nhan_nuoi', 'adopt',
        'funny', 'hai_huoc', 'dang_yeu', 'cute', 'meme', 'trending', 
        'khoe_pet', 'trai_nghiem', 'review', 'checkin', 'coffee_pet'
    ]

    core_keywords.sort(key=len, reverse=True)
    
    segmented = tag
    for word in core_keywords:
        if word in segmented:
            segmented = segmented.replace(word, f" {word} ")
    
    # Bước cực kỳ quan trọng: Chuẩn hóa từ không dấu phổ biến để AI không nhầm lẫn
    # 'cho' -> 'chó', 'meo' -> 'mèo', 'than' -> 'thằn'
    result = " ".join(segmented.split())
    result = result.replace('cho', 'chó').replace('meo', 'mèo').replace('than', 'thằn')
    
    return result

def get_engine():
    return create_engine(f"postgresql://{DB_CONFIG['user']}:{DB_CONFIG['pass']}@{DB_CONFIG['host']}:{DB_CONFIG['port']}/{DB_CONFIG['name']}")

def run_ai_analyzer():
    engine = get_engine()
    print("--- [AI ENGINE] Phân tích xu hướng sở thích... ---")
    
    try:
        # 2. LẤY DỮ LIỆU TƯƠNG TÁC (3 ngày gần nhất + Nuôi pet)
        query_interact = """
            SELECT user_id, weight, metadata, action_type, created_at
            FROM user_interactions 
            WHERE (action_type IN ('like', 'comment', 'share', 'view') AND created_at > NOW() - INTERVAL '3 days')
               OR (action_type IN ('own_pet', 'follow_pet'))
        """
        df_interact = pd.read_sql(query_interact, engine)
        
        # Lấy kho tag hệ thống để so sánh mở rộng
        all_system_tags = pd.read_sql("SELECT name FROM tags", engine)['name'].tolist()

        if df_interact.empty or not all_system_tags:
            print("[Thông báo] Không có tương tác mới để phân tích.")
            return

        # 3. TRÍCH XUẤT ĐIỂM GỐC TỪ HÀNH VI
        data_list = []
        now = datetime.now()
        for _, row in df_interact.iterrows():
            meta = row['metadata']
            if not meta: continue
            if isinstance(meta, str): meta = json.loads(meta)
            tags = meta.get('tags', [])
            
            if isinstance(tags, list):
                # Trọng số: Nuôi pet (5.0), Tương tác mới (1.5), Cũ hơn (1.0)
                weight = row['weight'] * 5.0 if row['action_type'] == 'own_pet' else \
                         (row['weight'] * 1.5 if (now - row['created_at']).days < 1 else row['weight'])
                for t in tags:
                    if t: data_list.append({'user_id': row['user_id'], 'tag_name': str(t).strip().lower(), 'score': weight})

        df_user_raw = pd.DataFrame(data_list).groupby(['user_id', 'tag_name'])['score'].sum().reset_index()

        # 4. LOGIC VECTOR HÓA: MỞ RỘNG SỞ THÍCH THÔNG MINH
        user_tags_raw = df_user_raw['tag_name'].unique().tolist()
        
        # Tách từ và chuẩn hóa cho cả tag user và tag hệ thống
        user_tags_seg = [segment_hashtag(t) for t in user_tags_raw]
        system_tags_seg = [segment_hashtag(t) for t in all_system_tags]

        # Chuyển đổi sang Vector
        u_embs = model.encode(user_tags_seg, convert_to_tensor=True)
        s_embs = model.encode(system_tags_seg, convert_to_tensor=True)
        cosine_scores = util.cos_sim(u_embs, s_embs)

        expanded_data = []
        for i, u_tag in enumerate(user_tags_raw):
            # CẢI TIẾN: Ngưỡng thích ứng. Từ ngắn (như 'cho') cần ngưỡng vừa phải để bắt được 'poodle'
            u_segmented = user_tags_seg[i]
            threshold = 0.72 if len(u_segmented) <= 4 else 0.78 

            for j, s_tag in enumerate(all_system_tags):
                similarity = cosine_scores[i][j].item()
                
                if similarity > threshold:
                    target_users = df_user_raw[df_user_raw['tag_name'] == u_tag]
                    for _, u_row in target_users.iterrows():
                        # Dùng mũ 2 thay vì mũ 4 để giữ lại sức mạnh cho các tag liên quan như poodle
                        penalty = (similarity ** 2) 
                        bonus_score = u_row['score'] if u_tag == s_tag else (u_row['score'] * penalty * 0.5)
                        
                        if bonus_score > 0.05:
                            expanded_data.append({'user_id': u_row['user_id'], 'tag_name': s_tag, 'score': bonus_score})

       # 5. GOM NHÓM LẠI VÀ GHI ĐÈ VÀO USER INTEREST PROFILE
        if expanded_data:
            df_final = pd.DataFrame(expanded_data).groupby(['user_id', 'tag_name'])['score'].sum().reset_index()

            with engine.begin() as conn:
                # --- CẢI TIẾN: Reset điểm của những người dùng có trong danh sách phân tích ---
                # Việc này đảm bảo nếu họ Unlike sạch bách thì điểm sẽ về 0 thay vì giữ điểm cũ
                active_users = df_final['user_id'].unique().tolist()
                if active_users:
                    conn.execute(
                        text("UPDATE user_interest_profiles SET score = 0 WHERE user_id IN :users"),
                        {"users": tuple(active_users)}
                    )

                for _, row in df_final.iterrows():
                    upsert_sql = text("""
                        INSERT INTO user_interest_profiles (id, user_id, tag_name, score, updated_at)
                        VALUES (:id, :user_id, :tag_name, :score, NOW())
                        ON CONFLICT (user_id, tag_name) 
                        DO UPDATE SET score = EXCLUDED.score, updated_at = NOW();
                    """)
                    conn.execute(upsert_sql, {
                        'id': str(uuid.uuid4()), 
                        'user_id': row['user_id'], 
                        'tag_name': row['tag_name'], 
                        'score': row['score']
                    })
                
                # (Tùy chọn) Xóa các bản ghi có score = 0 để làm sạch DB
                conn.execute(text("DELETE FROM user_interest_profiles WHERE score <= 0"))
        
        print(f"--- [AI ENGINE] HOÀN TẤT: Đã cập nhật hồ sơ cho {len(df_interact['user_id'].unique())} người dùng ---")

    except Exception as e:
        print(f"[LỖI HỆ THỐNG] {str(e)}")

if __name__ == "__main__":
    run_ai_analyzer()