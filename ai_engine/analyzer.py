import pandas as pd
from sqlalchemy import create_engine, text
import uuid
import json
from datetime import datetime
from sentence_transformers import SentenceTransformer, util
import torch

# --- CẤU HÌNH ---
DB_CONFIG = {
    'user': 'postgres',
    'pass': '123456',
    'host': '127.0.0.1',
    'port': '5432',
    'name': 'PetSocial'
}

# Khởi tạo Model (Nên để global để tránh load lại nhiều lần)
print("--- [AI ENGINE] Đang khởi động mô hình NLP... ---")
model = SentenceTransformer('paraphrase-multilingual-MiniLM-L12-v2')

def get_engine():
    return create_engine(f"postgresql://{DB_CONFIG['user']}:{DB_CONFIG['pass']}@{DB_CONFIG['host']}:{DB_CONFIG['port']}/{DB_CONFIG['name']}")

# --- TỐI ƯU HÀM TÁCH HASHTAG ---
def segment_hashtag(tag):
    tag = tag.lower().replace('#', '').strip()
    # Danh sách từ khóa cốt lõi (Có thể mở rộng thêm)
    replacements = {
    # Nhóm loài chính (Core Species)
    'cho': 'chó', 'meo': 'mèo', 'thanlan': 'thằn lằn', 'chim': 'chim', 
    'ca': 'cá', 'chuot': 'chuột', 'tho': 'thỏ', 'rua': 'rùa', 'ran': 'rắn',
    'hamster': 'chuột hamster', 'soc': 'sóc', 'nhim': 'nhím', 'ba_sat': 'bò sát',

    # Nhóm giống chó phổ biến (Dog Breeds)
    'poodle': 'chó poodle', 'pug': 'chó pug', 'husky': 'chó husky', 
    'corgi': 'chó corgi', 'shiba': 'chó shiba', 'phoc': 'chó phốc', 
    'golden': 'chó golden', 'becgie': 'chó becgie', 'pitbull': 'chó pitbull',

    # Nhóm sức khỏe & Y tế (Health & Medical)
    'kham': 'khám bệnh', 'tiem': 'tiêm phòng', 'vacxin': 'vắc xin', 
    'trietsan': 'triệt sản', 'thuy': 'thú y', 'giun': 'tẩy giun', 
    've': 'diệt ve rận', 'suckhoe': 'sức khỏe', 'benh': 'bệnh lý',

    # Nhóm dịch vụ & Chăm sóc (Services & Care)
    'tam': 'tắm rửa', 'tia': 'cắt tỉa lông', 'spa': 'spa thú cưng', 
    'grooming': 'grooming', 'massage': 'massage thú cưng', 'huanluyen': 'huấn luyện',
    'day': 'dạy thú cưng', 'khachsan': 'khách sạn thú cưng', 'thue': 'thuê trông pet',

    # Nhóm đồ ăn & Dinh dưỡng (Food & Nutrition)
    'thucan': 'thức ăn', 'pate': 'pate', 'hat': 'thức ăn hạt', 
    'xuong': 'xương gặm', 'banhthuong': 'bánh thưởng', 'snack': 'snack thú cưng',
    'dinhduong': 'dinh dưỡng', 'sua': 'sữa thú cưng',

    # Nhóm đồ dùng & Phụ kiện (Accessories)
    'chuong': 'chuồng', 'long': 'lồng', 'vongco': 'vòng cổ', 'daydat': 'dây dắt',
    'ao': 'áo quần', 'thoitrang': 'thời trang', 'balo': 'balo vận chuyển', 
    'cat': 'cát vệ sinh', 'khay': 'khay vệ sinh', 'dochoi': 'đồ chơi',

    # Nhóm cộng đồng & Cảm xúc (Community & Emotion)
    'cuutro': 'cứu trợ', 'nhannuoi': 'nhận nuôi', 'adopt': 'nhận nuôi', 
    'offline': 'offline hội nhóm', 'dangyeu': 'đáng yêu', 'haihuoc': 'hài hước',
    'meme': 'ảnh chế thú cưng', 'khoe': 'khoe thú cưng'
    }
    
    # Tách từ đơn giản dựa trên dictionary
    result = tag
    for eng, vie in replacements.items():
        if eng in result:
            result = result.replace(eng, f" {vie} ")
    
    return " ".join(result.split())

def run_ai_analyzer():
    engine = get_engine()
    
    try:
        # 1. LẤY DỮ LIỆU TƯƠNG TÁC
        # Sử dụng trọng số thời gian: Tương tác càng mới điểm càng cao
        query = """
            SELECT user_id, weight, metadata, action_type, created_at,
                   EXTRACT(EPOCH FROM (NOW() - created_at))/3600 as hours_ago
            FROM user_interactions 
            WHERE created_at > NOW() - INTERVAL '7 days'
        """
        df_interact = pd.read_sql(query, engine)
        all_system_tags = pd.read_sql("SELECT name FROM tags", engine)['name'].tolist()

        if df_interact.empty: return

        # 2. XỬ LÝ TRỌNG SỐ (TIME DECAY)
        data_list = []
        for _, row in df_interact.iterrows():
            meta = row['metadata']
            if not meta: continue
            if isinstance(meta, str): meta = json.loads(meta)
            tags = meta.get('tags', [])

            # Công thức Decay: Giảm 10% sau mỗi 24h
            time_decay = 0.9 ** (row['hours_ago'] / 24)
            base_score = row['weight'] * time_decay
            
            # Bonus đặc biệt cho hành động sở hữu thú cưng
            if row['action_type'] == 'own_pet': base_score *= 5

            for t in tags:
                data_list.append({
                    'user_id': row['user_id'], 
                    'tag_name': str(t).strip().lower(), 
                    'score': base_score
                })

        # Gom nhóm điểm thô
        df_raw = pd.DataFrame(data_list).groupby(['user_id', 'tag_name'])['score'].sum().reset_index()

        # 3. VECTOR HÓA & MỞ RỘNG (VECTOR EXPANSION)
        user_tags = df_raw['tag_name'].unique().tolist()
        u_embs = model.encode([segment_hashtag(t) for t in user_tags], convert_to_tensor=True)
        s_embs = model.encode([segment_hashtag(t) for t in all_system_tags], convert_to_tensor=True)
        
        cosine_scores = util.cos_sim(u_embs, s_embs)

        # 4. TẠO PROFILE MỚI
        final_profiles = []
        for i, u_tag in enumerate(user_tags):
            for j, s_tag in enumerate(all_system_tags):
                similarity = cosine_scores[i][j].item()
                
                if similarity > 0.75: # Ngưỡng tương đồng
                    users_with_tag = df_raw[df_raw['tag_name'] == u_tag]
                    for _, u_row in users_with_tag.iterrows():
                        final_profiles.append({
                            'user_id': u_row['user_id'],
                            'tag_name': s_tag,
                            'score': u_row['score'] * (similarity ** 2)
                        })

        # 5. CẬP NHẬT DATABASE (BATCH UPSERT)
        if final_profiles:
            df_final = pd.DataFrame(final_profiles).groupby(['user_id', 'tag_name'])['score'].sum().reset_index()
            
            with engine.begin() as conn:
                # Xóa sạch điểm cũ của những user có tương tác mới để làm mới profile
                active_users = df_final['user_id'].unique().tolist()
                conn.execute(text("DELETE FROM user_interest_profiles WHERE user_id IN :u"), {"u": tuple(active_users)})
                
                # Insert hàng loạt
                df_final['id'] = [str(uuid.uuid4()) for _ in range(len(df_final))]
                df_final['updated_at'] = datetime.now()
                df_final.to_sql('user_interest_profiles', conn, if_exists='append', index=False, method='multi')

        print(f"--- [AI ENGINE] Đã cập nhật xong cho {len(active_users)} users ---")

    except Exception as e:
        print(f"Lỗi: {e}")

if __name__ == "__main__":
    run_ai_analyzer()