import pandas as pd
from sqlalchemy import create_engine,text
import json
import uuid
import sys

# 1. CẤU HÌNH KẾT NỐI 
DB_CONFIG = {
    'user': 'postgres',
    'pass': '123456',
    'host': '127.0.0.1',
    'port': '5432',
    'name': 'PetSocial'
}

def get_engine():
    conn_str = f"postgresql://{DB_CONFIG['user']}:{DB_CONFIG['pass']}@{DB_CONFIG['host']}:{DB_CONFIG['port']}/{DB_CONFIG['name']}"
    return create_engine(conn_str)

def run_ai_analyzer():
    engine = get_engine()
    print("--- [AI ENGINE] Bắt đầu phân tích tương tác người dùng... ---")
    
    try:
        # 2. Đọc dữ liệu từ bảng interactions
        query = "SELECT user_id, weight, metadata FROM user_interactions"
        df = pd.read_sql(query, engine)

        if df.empty:
            print("[Thông báo] Bảng tương tác trống. Không có gì để phân tích.")
            return

        print(f"[1/3] Đang xử lý {len(df)} tương tác...")

        # 3. TRÍCH XUẤT TAGS
        data_list = []
        for index, row in df.iterrows():
            user_id = row['user_id']
            weight = row['weight']
            meta = row['metadata']

            # Kiểm tra nếu metadata bị NULL
            if meta is None:
                continue

            # Nếu Postgres trả về metadata dưới dạng String (thay vì dict), ta parse nó
            if isinstance(meta, str):
                try:
                    meta = json.loads(meta)
                except Exception:
                    continue

            # Lấy danh sách tags từ metadata
            # meta.get('tags') có thể trả về None nếu key 'tags' không tồn tại
            tags_raw = meta.get('tags')

            # KIỂM TRA QUAN TRỌNG: tags phải là một danh sách (Iterable)
            if tags_raw is None or not isinstance(tags_raw, list):
                continue

            # Lặp qua từng tag và thêm vào danh sách tính điểm
            for tag in tags_raw:
                if tag: # Bỏ qua nếu tag là chuỗi rỗng hoặc None
                    data_list.append({
                        'user_id': user_id,
                        'tag_name': str(tag).strip().lower(), # Chuẩn hóa tag (viết thường, xóa khoảng trắng)
                        'score': weight
                    })

        if not data_list:
            print("[Thông báo] Không tìm thấy tag hợp lệ nào trong metadata.")
            return

        # 4. TÍNH TOÁN ĐIỂM SỐ
        print("[2/3] Đang tính toán điểm sở thích...")
        df_tags = pd.DataFrame(data_list)
        
        # Gom nhóm theo User và Tag để cộng dồn điểm (Weight)
        df_final = df_tags.groupby(['user_id', 'tag_name'])['score'].sum().reset_index()

        # 5. CHUẨN BỊ DỮ LIỆU ĐỂ LƯU (Khớp với Schema: id, user_id, tag_name, score, timestamps)
        df_final['id'] = [str(uuid.uuid4()) for _ in range(len(df_final))]
        df_final['created_at'] = pd.Timestamp.now()
        df_final['updated_at'] = pd.Timestamp.now()

       # 6. GHI VÀO POSTGRESQL
        print(f"[3/3] Đang lưu {len(df_final)} kết quả vào bảng user_interest_profiles...")
        
        with engine.begin() as conn:
            # Sửa dòng này: Bao bọc chuỗi SQL trong hàm text()
            conn.execute(text("DELETE FROM user_interest_profiles"))
            
            # Ghi dữ liệu mới (Pandas to_sql thì vẫn giữ nguyên)
            df_final.to_sql(
                'user_interest_profiles', 
                con=conn, 
                if_exists='append', 
                index=False,
                method='multi'
            )
        print("--- [AI ENGINE] HOÀN THÀNH CẬP NHẬT SỞ THÍCH! ---")

    except Exception as e:
        print(f"[LỖI HỆ THỐNG] {str(e)}")

if __name__ == "__main__":
    run_ai_analyzer()