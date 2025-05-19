import cv2
import requests
import numpy as np
from facenet_pytorch import MTCNN

# ---------- CONFIG ----------
API_ENDPOINT = "http://localhost/Smart_Attendance_System/api.php"  # Your PHP endpoint
MIN_FACE_SIZE = 80  # Minimum face size in pixels

# Initialize MTCNN with optimized settings
mtcnn = MTCNN(
    keep_all=False,
    post_process=False,
    min_face_size=MIN_FACE_SIZE,
    device='cuda' if torch.cuda.is_available() else 'cpu'
)

def capture_and_recognize():
    cap = cv2.VideoCapture(0)
    if not cap.isOpened():
        print("Error: Could not open webcam")
        return

    print("Live Face Detection - Press SPACE to capture, Q to quit")
    
    while True:
        ret, frame = cap.read()
        if not ret:
            print("Error: Failed to capture frame")
            break

        # Display live feed with instructions
        cv2.putText(frame, "Press SPACE to capture", (10, 30),
                    cv2.FONT_HERSHEY_SIMPLEX, 0.8, (0, 255, 0), 2)
        cv2.imshow('Face Recognition Attendance', frame)

        key = cv2.waitKey(1)
        if key == ord('q'):
            break
        elif key == 32:  # SPACE key
            print("Processing captured frame...")
            process_frame(frame)
            break

    cap.release()
    cv2.destroyAllWindows()

def process_frame(frame):
    try:
        # Convert to RGB and detect face
        rgb_frame = cv2.cvtColor(frame, cv2.COLOR_BGR2RGB)
        boxes, _ = mtcnn.detect(rgb_frame)

        if boxes is None:
            print("No face detected. Please try again.")
            return

        # Extract the best face
        x1, y1, x2, y2 = map(int, boxes[0])
        face_img = frame[y1:y2, x1:x2]

        if face_img.size == 0:
            print("Invalid face crop. Please try again.")
            return

        # Prepare for API request
        _, img_encoded = cv2.imencode('.jpg', face_img)
        img_bytes = img_encoded.tobytes()

        # Send to PHP API
        response = requests.post(
            API_ENDPOINT,
            files={'face_image': ('capture.jpg', img_bytes, 'image/jpeg')},
            data={'action': 'recognize'}
        )

        handle_api_response(response)

    except Exception as e:
        print(f"Error during face processing: {str(e)}")

def handle_api_response(response):
    try:
        result = response.json()
        if result['status'] == 'success':
            user = result['user']
            print(f"\n✅ ATTENDANCE MARKED ✅")
            print(f"Name: {user['name']}")
            print(f"ID: {user['id']}")
            print(f"Confidence: {user['confidence']}%")
        elif result['status'] == 'not_found':
            print("❌ No matching student found in database")
        else:
            print(f"Error: {result.get('message', 'Unknown error')}")
    except ValueError:
        print("Invalid response from server")
    except Exception as e:
        print(f"Error handling response: {str(e)}")

if __name__ == "__main__":
    capture_and_recognize()