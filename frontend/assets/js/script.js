// === DOM elements ===
const videoElement = document.getElementById('videoElement');
const resultContainer = document.getElementById('recognition-result');
const userNameElem = document.getElementById('user-name');
const userIdElem = document.getElementById('user-id');
const captureButton = document.getElementById('start-recognition');
const statusBadge = document.getElementById('status-badge');

// === State ===
let videoStream = null;
let isProcessing = false;
let recognitionInterval = null;
const PROCESSING_INTERVAL = 2000; // 2 seconds between recognition attempts

// === Webcam Initialization ===
async function startWebcam() {
    try {
        if (videoStream) {
            videoStream.getTracks().forEach(track => track.stop());
        }

        videoStream = await navigator.mediaDevices.getUserMedia({
            video: {
                width: { ideal: 1280 },
                height: { ideal: 720 },
                facingMode: 'user'
            },
            audio: false
        });

        videoElement.srcObject = videoStream;

        await new Promise(resolve => {
            videoElement.onloadedmetadata = () => {
                videoElement.play();
                resolve();
            };
        });

        return true;
    } catch (error) {
        console.error("Webcam Error:", error);
        resultContainer.innerHTML = "Camera access denied. Please enable permissions.";
        return false;
    }
}

// === Image Capture ===
async function captureImage() {
    if (isProcessing) return;
    isProcessing = true;

    try {
        const canvas = document.createElement('canvas');
        canvas.width = videoElement.videoWidth;
        canvas.height = videoElement.videoHeight;
        const context = canvas.getContext('2d');

        context.drawImage(videoElement, 0, 0, canvas.width, canvas.height);

        resultContainer.innerHTML = "Processing...";
        statusBadge.className = "status-badge status-processing";

        const blob = await new Promise(resolve =>
            canvas.toBlob(resolve, 'image/jpeg', 0.8)
        );

        await sendImageToAPI(blob);
    } catch (error) {
        console.error("Capture Error:", error);
        resultContainer.innerHTML = "Error capturing image";
    } finally {
        isProcessing = false;
    }
}

// === API Communication (✅ Fixed Endpoint) ===
async function sendImageToAPI(imageBlob) {
    try {
        const formData = new FormData();
        formData.append('image', imageBlob, 'frame.jpg');

        // ✅ Fixed endpoint
        const response = await fetch('http://localhost:5000/recognize', {
            method: 'POST',
            body: formData
        });

        if (!response.ok) throw new Error(`API request failed (${response.status})`);

        const responseData = await response.json();

        if (responseData.status === 'Present') {
            updateRecognitionUI(responseData);
            await markAttendance(responseData.user_id);
        } else {
            showNoMatch();
        }
    } catch (error) {
        console.error("API Error:", error);
        resultContainer.innerHTML = "Recognition service unavailable.";
        showNoMatch();
    }
}

// === UI Update ===
function updateRecognitionUI(data) {
    userNameElem.textContent = data.name;
    userIdElem.textContent = data.user_id;
    resultContainer.innerHTML = `Recognized: ${data.name}`;
    statusBadge.className = "status-badge status-present";
    statusBadge.textContent = "Present";
}

function showNoMatch() {
    userNameElem.textContent = "Unknown";
    userIdElem.textContent = "-";
    statusBadge.className = "status-badge status-unknown";
    statusBadge.textContent = "Unknown";
}

// === Attendance Marking ===
async function markAttendance(userId) {
    try {
        const response = await fetch('mark_attendance.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                user_id: userId,
                timestamp: new Date().toISOString()
            })
        });

        const data = await response.json();
        if (data.status !== 'success') {
            throw new Error(data.message || 'Attendance marking failed');
        }
        console.log("Attendance marked successfully");
    } catch (error) {
        console.error("Attendance Error:", error);
        resultContainer.innerHTML += " (Attendance not recorded)";
    }
}

// === Toggle Recognition ===
function toggleRecognition() {
    if (recognitionInterval) {
        stopRecognition();
    } else {
        startRecognition();
    }
}

function startRecognition() {
    if (!videoStream) {
        startWebcam();
    }

    recognitionInterval = setInterval(captureImage, PROCESSING_INTERVAL);
    captureButton.textContent = "Stop Recognition";
    captureButton.classList.remove('btn-primary');
    captureButton.classList.add('btn-danger');
}

function stopRecognition() {
    clearInterval(recognitionInterval);
    recognitionInterval = null;
    captureButton.textContent = "Start Recognition";
    captureButton.classList.remove('btn-danger');
    captureButton.classList.add('btn-primary');
}

// === Event Listeners ===
captureButton.addEventListener('click', toggleRecognition);

window.addEventListener('beforeunload', () => {
    stopRecognition();
    if (videoStream) {
        videoStream.getTracks().forEach(track => track.stop());
    }
});

// === Init ===
(async function init() {
    await startWebcam();
})();
