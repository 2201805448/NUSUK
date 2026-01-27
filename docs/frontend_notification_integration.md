# Frontend Notification Integration Guide (Vue.js)

This guide outlines how to integrate Firebase Cloud Messaging (FCM) notifications and the backend notification system into the Vue.js frontend.

## Prerequisites

1.  **Firebase Project**: Ensure you have the Firebase config object for the frontend (from the Firebase Console -> Project Settings -> General -> Your Apps -> Web App).
2.  **Dependencies**: You will likely need `firebase` installed in your project:
    ```bash
    npm install firebase
    ```

## 1. Firebase Initialization & Token Retrieval

Create a service file (e.g., `src/services/firebase.js`) to initialize Firebase and handle messaging.

```javascript
// src/services/firebase.js
import { initializeApp } from "firebase/app";
import { getMessaging, getToken, onMessage } from "firebase/messaging";

const firebaseConfig = {
  apiKey: "YOUR_API_KEY",
  authDomain: "YOUR_AUTH_DOMAIN",
  projectId: "YOUR_PROJECT_ID",
  storageBucket: "YOUR_STORAGE_BUCKET",
  messagingSenderId: "YOUR_MESSAGING_SENDER_ID",
  appId: "YOUR_APP_ID"
};

const app = initializeApp(firebaseConfig);
const messaging = getMessaging(app);

export const getFcmToken = async () => {
  try {
    const currentToken = await getToken(messaging, { 
      vapidKey: 'YOUR_PUBLIC_VAPID_KEY' // Get this from Firebase Console -> Cloud Messaging -> Web Configuration
    });
    
    if (currentToken) {
      console.log('FCM Token:', currentToken);
      return currentToken;
    } else {
      console.log('No registration token available. Request permission to generate one.');
      return null;
    }
  } catch (err) {
    console.log('An error occurred while retrieving token. ', err);
    return null;
  }
};

export const onMessageListener = () =>
  new Promise((resolve) => {
    onMessage(messaging, (payload) => {
      resolve(payload);
    });
  });
```

## 2. Login Integration

Update your login function (e.g., in your Pinia store or Vuex action) to retrieve the FCM token *before* calling the login API, and include it in the request body.

**Endpoint**: `POST /api/login`

**Request Body**:
```json
{
  "email": "user@example.com",
  "password": "secretparams",
  "fcm_token": "THE_FCM_TOKEN_RETRIEVED_FROM_FIREBASE"
}
```

**Example Login Action (Pinia/Axios)**:

```javascript
// src/stores/auth.js
import axios from 'axios';
import { getFcmToken } from '@/services/firebase';

export const useAuthStore = defineStore('auth', {
  actions: {
    async login(credentials) {
      try {
        // 1. Get FCM Token
        const fcmToken = await getFcmToken();

        // 2. Prepare payload
        const payload = {
          email: credentials.email,
          password: credentials.password,
          fcm_token: fcmToken // Pass null if failed, backend handles nullable
        };

        // 3. Call API
        const response = await axios.post('/api/login', payload);
        
        // 4. Handle success (save token, user, etc.)
        this.user = response.data.user;
        this.token = response.data.token;
        
      } catch (error) {
        console.error('Login failed:', error);
        throw error;
      }
    }
  }
});
```

## 3. Handling Notifications

You have two channels for notifications:

### A. Foreground Push Notifications (In-App Toast)
When the app is open in the foreground, the service worker usually doesn't show a system notification automatically. You need to listen for the message and show a UI element (toast/snackbar).

```javascript
// App.vue or a persistent Layout component
import { onMounted } from 'vue';
import { onMessageListener } from '@/services/firebase';
import { useToast } from 'vue-toastification'; // Example toast lib

const toast = useToast();

onMounted(() => {
  onMessageListener().then(payload => {
    console.log('Foreground notification received:', payload);
    const { title, body } = payload.notification;
    toast.info(`${title}: ${body}`);
    
    // Optional: Refresh notification list from API
    // store.fetchNotifications();
  });
});
```

### B. Background Notifications
These are handled by the Firebase Service Worker (`firebase-messaging-sw.js`). You generally don't need Vue code for this, just ensuring the service worker is correctly registered in `public/`.

### C. In-App Notification List (API)
The backend also stores notifications in the database. You can fetch these to show a "Notification Center" list.

**Endpoint**: `GET /api/notifications`
**Headers**: `Authorization: Bearer <auth_token>`

```javascript
// Example fetch method
async fetchNotifications() {
  const response = await axios.get('/api/notifications');
  this.notifications = response.data; // Paginates by default
}
```

**Mark as Read**:
**Endpoint**: `PUT /api/notifications/{id}/read`

```javascript
async markAsRead(id) {
  await axios.put(`/api/notifications/${id}/read`);
  // Update local state to show as read
}
```
