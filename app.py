from flask import Flask, request, jsonify
from flask_cors import CORS
from deepface import DeepFace
import base64
from io import BytesIO
from PIL import Image
import logging
import os
import cv2
import numpy as np

# Set up logging
logging.basicConfig(level=logging.DEBUG)

app = Flask(__name__)
CORS(app)

# Define folders for saving webcam and facial images temporarily
WEBCAMS_FOLDER = 'webcams'
TEACHERS_PICS_FOLDER = 'teachersPics'
STUDENTS_PICS_FOLDER = 'uploads'  # Folder for storing students' facial data
app.config['WEBCAMS_FOLDER'] = os.path.join(os.getcwd(), WEBCAMS_FOLDER)
app.config['TEACHERS_PICS_FOLDER'] = os.path.join(os.getcwd(), TEACHERS_PICS_FOLDER)
app.config['STUDENTS_PICS_FOLDER'] = os.path.join(os.getcwd(), STUDENTS_PICS_FOLDER)

# Ensure that the folders exist
os.makedirs(app.config['WEBCAMS_FOLDER'], exist_ok=True)
os.makedirs(app.config['TEACHERS_PICS_FOLDER'], exist_ok=True)
os.makedirs(app.config['STUDENTS_PICS_FOLDER'], exist_ok=True)

def analyze_texture(image_path):
    """
    Analyze image texture to detect if it's a real face or a printed/photo face.
    """
    image = cv2.imread(image_path)
    gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)
    variance = cv2.Laplacian(gray, cv2.CV_64F).var()
    logging.debug(f"Laplacian variance (texture): {variance}")
    return variance > 50  # Threshold can be adjusted

def detect_liveness(image_path):
    """
    Enhanced liveness detection combining texture and additional analysis.
    """
    try:
        # Texture analysis
        if not analyze_texture(image_path):
            logging.warning("Liveness failed: Texture analysis detected a spoof.")
            return False
        
        # Optional: Add advanced checks for motion, blinking, or depth here
        # Example: Ask user to blink or turn their head and capture video.

        return True
    except Exception as e:
        logging.error(f"Liveness detection error: {e}")
        return False

def decode_base64_image(base64_data):
    """Remove the data URI prefix and decode base64 image data."""
    if base64_data.startswith("data:image"):
        base64_data = base64_data.split(',')[1]  # Remove the prefix
    return base64.b64decode(base64_data)

def save_image_from_base64(image_data, save_path):
    """Save the image decoded from base64 to the given file path."""
    try:
        image_bytes = decode_base64_image(image_data)
        image = Image.open(BytesIO(image_bytes))
        image = image.convert('RGB')
        image.save(save_path, 'JPEG')
        return True
    except Exception as e:
        logging.error(f"Error saving image from base64: {e}")
        return False

@app.route('/verify', methods=['POST'])
def verify_teacher():
    try:
        data = request.get_json()
        teacher_email = data.get('email')
        captured_image_data = data.get('capturedImage')  # Captured webcam image in base64
        stored_facial_data = data.get('facialData')  # Teacher's stored facial data in base64

        if not teacher_email or not captured_image_data or not stored_facial_data:
            return jsonify({'error': "Missing required fields: email, capturedImage, or facialData"}), 400

        # Save the captured image (webcam image)
        temp_captured_image_path = os.path.join(app.config['WEBCAMS_FOLDER'], 'temp_captured_image.jpg')
        if not save_image_from_base64(captured_image_data, temp_captured_image_path):
            return jsonify({'error': 'Error processing captured image'}), 500

        # Perform liveness detection
        if not detect_liveness(temp_captured_image_path):
            return jsonify({'error': "Liveness detection failed. Possible spoofing detected."}), 403

        # Fetch the stored facial image for the teacher
        stored_facial_image_path = os.path.join(app.config['TEACHERS_PICS_FOLDER'], stored_facial_data)
        if not os.path.exists(stored_facial_image_path):
            return jsonify({'error': "Stored facial image not found for the given teacher."}), 404

        # Verify the captured image with the stored facial image
        result = DeepFace.verify(temp_captured_image_path, stored_facial_image_path)
        return jsonify(result)
    except Exception as e:
        return jsonify({'error': f"Unexpected error: {str(e)}"}), 500


@app.route('/verify_student', methods=['POST'])
def verify_student():
    try:
        data = request.get_json()
        student_id = data.get('studentId')
        captured_image_data = data.get('capturedImage')
        stored_facial_data = data.get('facialData')

        if not student_id or not captured_image_data or not stored_facial_data:
            return jsonify({'error': 'Missing data'}), 400

        # Save and verify images
        temp_captured_image_path = os.path.join(app.config['WEBCAMS_FOLDER'], f'{student_id}_captured_image.jpg')
        if not save_image_from_base64(captured_image_data, temp_captured_image_path):
            return jsonify({'error': 'Error processing captured image'}), 500

        stored_facial_image_path = os.path.join(app.config['STUDENTS_PICS_FOLDER'], stored_facial_data)
        if not os.path.exists(stored_facial_image_path):
            return jsonify({'error': 'Stored facial data not found'}), 404

        try:
            result = DeepFace.verify(temp_captured_image_path, stored_facial_image_path)
        except Exception as e:
            return jsonify({'error': f'Face verification failed: {e}'}), 500

        return jsonify({'verified': result['verified']})

    except Exception as e:
        return jsonify({'error': str(e)}), 500
    
if __name__ == '__main__':
    app.run(debug=True)
