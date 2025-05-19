from flask import Flask, request, jsonify
from flask_cors import CORS
import cv2
import numpy as np
import torch
from facenet_pytorch import InceptionResnetV1, MTCNN
import os
import logging
import time
import re

# Configure logging
logging.basicConfig(level=logging.INFO, format='%(asctime)s - %(levelname)s - %(message)s')
logger = logging.getLogger(__name__)

app = Flask(__name__)
CORS(app)

class Config:
    DATASET_PATH = r"C:\xampp\htdocs\Smart_Attendance_System\frontend\dataset"
    UPLOADS_PATH = r"C:\xampp\htdocs\Smart_Attendance_System\frontend\uploads"
    THRESHOLD = 0.65  # Similarity threshold
    MIN_FACE_SIZE = 100  # Minimum face size in pixels
    MAX_IMAGE_SIZE = 1024
    DEVICE = 'cuda' if torch.cuda.is_available() else 'cpu'

# Initialize models with error handling
try:
    logger.info("Initializing face recognition models...")
    mtcnn = MTCNN(
        keep_all=False,
        min_face_size=Config.MIN_FACE_SIZE,
        device=Config.DEVICE
    )
    resnet = InceptionResnetV1(
        pretrained='vggface2',
        classify=False,
        device=Config.DEVICE
    ).eval()
except Exception as e:
    logger.error(f"Model initialization failed: {str(e)}")
    raise

def get_face_encoding(image_rgb):
    """Robust face detection and encoding"""
    try:
        # Detect face
        face = mtcnn(image_rgb)
        if face is None:
            return None
            
        # Get embedding with normalization
        with torch.no_grad():
            embedding = resnet(face.unsqueeze(0).to(Config.DEVICE))
            embedding = embedding.detach().cpu().numpy().flatten()
            return embedding / np.linalg.norm(embedding)
    except Exception as e:
        logger.error(f"Face encoding error: {str(e)}")
        return None

def parse_filename(filename):
    """Parse the filename to extract user information in format [name]_[rollno]_[id].jpg"""
    try:
        # Remove file extension
        basename = os.path.splitext(filename)[0]
        
        # Split into parts
        parts = basename.split('_')
        
        # Should have at least 3 parts (name, rollno, id)
        if len(parts) < 3:
            return None
            
        # Name might contain underscores, so join all parts except last two
        name = ' '.join(parts[:-2])
        roll_no = parts[-2]
        user_id = parts[-1]
        
        return {
            'id': user_id,
            'name': name,
            'roll_no': roll_no,
            'filename': filename
        }
    except Exception as e:
        logger.error(f"Error parsing filename {filename}: {str(e)}")
        return None

def load_known_faces():
    """Load and validate known faces"""
    known_encodings = []
    known_metadata = []
    
    for folder in [Config.DATASET_PATH, Config.UPLOADS_PATH]:
        if not os.path.exists(folder):
            logger.warning(f"Directory not found: {folder}")
            continue
            
        for filename in sorted(os.listdir(folder)):
            if filename.lower().endswith(('.jpg', '.jpeg', '.png')):
                try:
                    # Skip profile photos (those with '_photo' suffix)
                    if '_photo' in filename.lower():
                        continue
                        
                    # Parse filename using the correct format
                    user_info = parse_filename(filename)
                    if not user_info:
                        continue
                        
                    img_path = os.path.join(folder, filename)
                    
                    # Load and preprocess image
                    img = cv2.imread(img_path)
                    if img is None:
                        continue
                        
                    # Resize maintaining aspect ratio
                    h, w = img.shape[:2]
                    if max(h, w) > Config.MAX_IMAGE_SIZE:
                        scale = Config.MAX_IMAGE_SIZE / max(h, w)
                        img = cv2.resize(img, (0,0), fx=scale, fy=scale)
                    
                    img_rgb = cv2.cvtColor(img, cv2.COLOR_BGR2RGB)
                    encoding = get_face_encoding(img_rgb)
                    
                    if encoding is not None:
                        known_encodings.append(encoding)
                        known_metadata.append({
                            'id': user_info['id'],
                            'name': user_info['name'],
                            'roll_no': user_info['roll_no'],
                            'path': img_path
                        })
                        logger.info(f"Loaded: {user_info['name']} (Roll: {user_info['roll_no']}, ID: {user_info['id']})")
                except Exception as e:
                    logger.error(f"Error loading {filename}: {str(e)}")
    
    if not known_encodings:
        logger.error("No valid face encodings loaded! Check your images.")
    else:
        logger.info(f"Successfully loaded {len(known_encodings)} face encodings")
    
    return np.array(known_encodings), known_metadata

# Load known faces at startup
logger.info("Loading known faces...")
known_encodings, known_metadata = load_known_faces()

@app.route('/recognize', methods=['POST'])
def recognize():
    """Face recognition endpoint"""
    start_time = time.time()
    
    if 'image' not in request.files:
        return jsonify({'success': False, 'error': 'No image provided'}), 400
        
    try:
        # Read and validate image
        file = request.files['image']
        img = cv2.imdecode(np.frombuffer(file.read(), 
                                         np.uint8), cv2.IMREAD_COLOR)
        if img is None:
            return jsonify({'success': False, 'error': 'Invalid image'}), 400
            
        # Resize maintaining aspect ratio
        h, w = img.shape[:2]
        if max(h, w) > Config.MAX_IMAGE_SIZE:
            scale = Config.MAX_IMAGE_SIZE / max(h, w)
            img = cv2.resize(img, (0,0), fx=scale, fy=scale)
            
        img_rgb = cv2.cvtColor(img, cv2.COLOR_BGR2RGB)
        
        # Get face encoding
        encoding = get_face_encoding(img_rgb)
        if encoding is None:
            return jsonify({
                'success': True,
                'recognized': False,
                'message': 'No face detected or poor quality',
                'processing_time': round(time.time() - start_time, 3)
            })
        
        # Compare with known faces
        if len(known_encodings) == 0:
            return jsonify({
                'success': False,
                'error': 'No known faces loaded',
                'processing_time': round(time.time() - start_time, 3)
            })
        
        # Calculate cosine similarities
        similarities = np.dot(known_encodings, encoding)
        best_match_idx = np.argmax(similarities)
        best_similarity = similarities[best_match_idx]
        
        # Apply threshold
        if best_similarity < Config.THRESHOLD:
            return jsonify({
                'success': True,
                'recognized': False,
                'message': f'No confident match (similarity: {best_similarity:.2f})',
                'processing_time': round(time.time() - start_time, 3)
            })
            
        # Return best match with roll number
        user = known_metadata[best_match_idx]
        return jsonify({
            'success': True,
            'recognized': True,
            'user': {
                'id': user['id'],
                'name': user['name'],
                'roll_no': user['roll_no'],
                'confidence': float(best_similarity)
            },
            'processing_time': round(time.time() - start_time, 3)
        })
        
    except Exception as e:
        logger.error(f"Recognition error: {str(e)}")
        return jsonify({
            'success': False,
            'error': 'Processing failed',
            'details': str(e),
            'processing_time': round(time.time() - start_time, 3)
        }), 500

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000, debug=True)