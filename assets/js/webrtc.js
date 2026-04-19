// WebRTC Configuration
const configuration = {
    iceServers: [
        { urls: 'stun:stun.l.google.com:19302' },
        { urls: 'stun:stun1.l.google.com:19302' }
    ]
};

let localStream = null;
let peerConnection = null;
let currentCallId = null;
let callType = 'voice';
let isMuted = false;
let isSpeakerOn = true;
let callStartTime = null;
let durationInterval = null;

// Start call
async function startCall(receiverId, type) {
    callType = type;
    
    try {
        // Initiate call on server
        const response = await fetch('api/call_handler.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=initiate&receiver_id=${receiverId}&call_type=${type}`
        });
        
        const data = await response.json();
        
        if (!data.success) {
            alert(data.error || 'Gagal memulai panggilan');
            return;
        }
        
        currentCallId = data.call_id;
        
        // Get local media
        const constraints = {
            audio: true,
            video: type === 'video'
        };
        
        localStream = await navigator.mediaDevices.getUserMedia(constraints);
        
        // Show active call modal
        showActiveCallModal(receiverId);
        
        // Create peer connection
        await createPeerConnection(receiverId);
        
        // Add local stream
        localStream.getTracks().forEach(track => {
            peerConnection.addTrack(track, localStream);
        });
        
        // Create and send offer
        const offer = await peerConnection.createOffer();
        await peerConnection.setLocalDescription(offer);
        
        // Send offer to server (via WebSocket in production)
        // For now, we'll use polling
        
    } catch (error) {
        console.error('Error starting call:', error);
        alert('Gagal memulai panggilan: ' + error.message);
    }
}

// Create peer connection
async function createPeerConnection(receiverId) {
    peerConnection = new RTCPeerConnection(configuration);
    
    // ICE candidate event
    peerConnection.onicecandidate = (event) => {
        if (event.candidate) {
            // Send candidate to other peer
            console.log('ICE candidate:', event.candidate);
        }
    };
    
    // Track event (receive remote stream)
    peerConnection.ontrack = (event) => {
        const remoteVideo = document.getElementById('remoteVideo');
        if (remoteVideo) {
            remoteVideo.srcObject = event.streams[0];
        }
    };
    
    // Connection state change
    peerConnection.onconnectionstatechange = () => {
        console.log('Connection state:', peerConnection.connectionState);
        if (peerConnection.connectionState === 'connected') {
            document.getElementById('callStatus').textContent = 'Connected';
            startCallTimer();
        } else if (peerConnection.connectionState === 'disconnected') {
            endCall();
        }
    };
}

// Show active call modal
function showActiveCallModal(receiverId) {
    const modal = document.getElementById('activeCallModal');
    const localVideo = document.getElementById('localVideo');
    
    // Get receiver info
    fetch(`api/get_user_info.php?id=${receiverId}`)
        .then(r => r.json())
        .then(user => {
            document.getElementById('activeCallAvatar').src = `uploads/${user.avatar || 'default-avatar.png'}`;
            document.getElementById('activeCallName').textContent = user.full_name || user.username;
        });
    
    if (callType === 'video' && localStream) {
        localVideo.srcObject = localStream;
        localVideo.style.display = 'block';
    } else {
        localVideo.style.display = 'none';
    }
    
    modal.style.display = 'flex';
}

// Accept incoming call
async function acceptCall() {
    if (!incomingCallData) return;
    
    document.getElementById('callModal').style.display = 'none';
    
    callType = incomingCallData.type;
    currentCallId = incomingCallData.callId;
    
    try {
        const constraints = {
            audio: true,
            video: callType === 'video'
        };
        
        localStream = await navigator.mediaDevices.getUserMedia(constraints);
        
        // Answer call on server
        await fetch('api/call_handler.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=answer&call_id=${currentCallId}`
        });
        
        showActiveCallModal(incomingCallData.callerId);
        await createPeerConnection(incomingCallData.callerId);
        
        localStream.getTracks().forEach(track => {
            peerConnection.addTrack(track, localStream);
        });
        
    } catch (error) {
        console.error('Error accepting call:', error);
    }
}

// Reject incoming call
async function rejectCall() {
    if (!incomingCallData) return;
    
    await fetch('api/call_handler.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=reject&call_id=${incomingCallData.callId}`
    });
    
    document.getElementById('callModal').style.display = 'none';
    incomingCallData = null;
}

// End call
async function endCall() {
    if (currentCallId) {
        const duration = callStartTime ? Math.floor((Date.now() - callStartTime) / 1000) : 0;
        
        await fetch('api/call_handler.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=end&call_id=${currentCallId}&duration=${duration}`
        });
    }
    
    cleanupCall();
}

// Cleanup call resources
function cleanupCall() {
    if (durationInterval) {
        clearInterval(durationInterval);
        durationInterval = null;
    }
    
    if (localStream) {
        localStream.getTracks().forEach(track => track.stop());
        localStream = null;
    }
    
    if (peerConnection) {
        peerConnection.close();
        peerConnection = null;
    }
    
    document.getElementById('activeCallModal').style.display = 'none';
    document.getElementById('callModal').style.display = 'none';
    
    currentCallId = null;
    callStartTime = null;
    incomingCallData = null;
}

// Start call timer
function startCallTimer() {
    callStartTime = Date.now();
    
    durationInterval = setInterval(() => {
        const duration = Math.floor((Date.now() - callStartTime) / 1000);
        const minutes = Math.floor(duration / 60);
        const seconds = duration % 60;
        
        document.getElementById('callDuration').textContent = 
            `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    }, 1000);
}

// Toggle mute
function toggleMute() {
    if (localStream) {
        const audioTrack = localStream.getAudioTracks()[0];
        if (audioTrack) {
            audioTrack.enabled = !audioTrack.enabled;
            isMuted = !audioTrack.enabled;
            
            const btn = document.getElementById('muteBtn');
            btn.innerHTML = isMuted ? 
                '<i class="fas fa-microphone-slash"></i>' : 
                '<i class="fas fa-microphone"></i>';
            btn.classList.toggle('muted', isMuted);
        }
    }
}

// Toggle speaker
function toggleSpeaker() {
    isSpeakerOn = !isSpeakerOn;
    const btn = document.getElementById('speakerBtn');
    btn.innerHTML = isSpeakerOn ? 
        '<i class="fas fa-volume-up"></i>' : 
        '<i class="fas fa-volume-off"></i>';
}

// Toggle video
function toggleVideo() {
    if (localStream) {
        const videoTrack = localStream.getVideoTracks()[0];
        if (videoTrack) {
            videoTrack.enabled = !videoTrack.enabled;
            
            const btn = document.getElementById('videoBtn');
            btn.innerHTML = videoTrack.enabled ? 
                '<i class="fas fa-video"></i>' : 
                '<i class="fas fa-video-slash"></i>';
        }
    }
}

// Show incoming call modal
function showIncomingCall(data) {
    incomingCallData = data;
    
    fetch(`api/get_user_info.php?id=${data.callerId}`)
        .then(r => r.json())
        .then(user => {
            document.getElementById('callerAvatar').src = `uploads/${user.avatar || 'default-avatar.png'}`;
            document.getElementById('callerName').textContent = user.full_name || user.username;
            document.getElementById('callType').textContent = 
                data.type === 'video' ? 'Video Call...' : 'Voice Call...';
        });
    
    document.getElementById('callModal').style.display = 'flex';
    
    // Play ringtone
    const audio = new Audio('assets/sounds/ringtone.mp3');
    audio.loop = true;
    audio.play().catch(e => console.log('Autoplay blocked'));
    
    // Auto reject after 30 seconds
    setTimeout(() => {
        if (document.getElementById('callModal').style.display === 'flex') {
            rejectCall();
        }
    }, 30000);
}