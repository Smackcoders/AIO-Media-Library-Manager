import React from 'react';
import ReactDOM from 'react-dom/client';
import AppTree from './FolderStructure/App_Tree';

const MOUNT_NODE_ID = 'my-react-root';


// Check frequently. WP Modal interactions are user-driven.
setInterval(checkAndMount, 500);

function checkAndMount() {
  const container = document.getElementById(MOUNT_NODE_ID);

  if (container) {
    // Check if already mounted to prevent double render
    if (!container.dataset.reactRootMounted) {
      console.log("Mounting AIOML React App...");
      container.dataset.reactRootMounted = "true";

      try {
        const root = ReactDOM.createRoot(container);
        console.log("Rendering AppTree...");
        root.render(
          <React.StrictMode>
            <AppTree />
          </React.StrictMode>
        );
      } catch (err) {
        console.error("AIOML React App Crash:", err);
        container.innerHTML = `<div style="color:red; padding:10px;">AIOML Error: ${err.message}</div>`;
      }
    }
  }
}

