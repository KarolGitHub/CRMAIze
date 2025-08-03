/**
 * CRMAIze PWA (Progressive Web App) functionality
 * Handles installation prompts, service worker, and mobile features
 */

class CRMAIzePWA {
  constructor() {
    this.deferredPrompt = null;
    this.isInstalled = false;
    this.isStandalone = false;
    this.touchStartX = 0;
    this.touchStartY = 0;
    
    this.init();
  }
  
  init() {
    this.checkInstallationStatus();
    this.registerServiceWorker();
    this.setupInstallPrompt();
    this.setupMobileFeatures();
    this.setupOfflineDetection();
    this.setupTouchGestures();
    this.setupKeyboardShortcuts();
  }
  
  // Check if app is installed or running in standalone mode
  checkInstallationStatus() {
    this.isStandalone = window.matchMedia('(display-mode: standalone)').matches ||
                      window.navigator.standalone ||
                      document.referrer.includes('android-app://');
    
    this.isInstalled = this.isStandalone;
    
    if (this.isStandalone) {
      document.body.classList.add('pwa-standalone');
      console.log('[PWA] Running in standalone mode');
    }
  }
  
  // Register service worker
  async registerServiceWorker() {
    if ('serviceWorker' in navigator) {
      try {
        const registration = await navigator.serviceWorker.register('/sw.js');
        console.log('[PWA] Service Worker registered:', registration);
        
        // Listen for updates
        registration.addEventListener('updatefound', () => {
          const newWorker = registration.installing;
          newWorker.addEventListener('statechange', () => {
            if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
              this.showUpdateAvailable();
            }
          });
        });
        
        // Handle messages from service worker
        navigator.serviceWorker.addEventListener('message', event => {
          this.handleServiceWorkerMessage(event);
        });
        
      } catch (error) {
        console.error('[PWA] Service Worker registration failed:', error);
      }
    }
  }
  
  // Setup PWA installation prompt
  setupInstallPrompt() {
    // Listen for beforeinstallprompt event
    window.addEventListener('beforeinstallprompt', event => {
      console.log('[PWA] Install prompt available');
      event.preventDefault();
      this.deferredPrompt = event;
      this.showInstallPrompt();
    });
    
    // Listen for app installed event
    window.addEventListener('appinstalled', event => {
      console.log('[PWA] App installed successfully');
      this.isInstalled = true;
      this.hideInstallPrompt();
      this.showInstallSuccess();
    });
  }
  
  // Show install prompt UI
  showInstallPrompt() {
    if (this.isInstalled || !this.deferredPrompt) return;
    
    const promptHtml = `
      <div class="pwa-install-prompt" id="installPrompt">
        <button class="close-btn" onclick="crmaizePWA.hideInstallPrompt()">&times;</button>
        <div style="display: flex; align-items: center; gap: 1rem;">
          <div style="font-size: 2rem;">📱</div>
          <div style="flex: 1;">
            <h4 style="margin: 0; color: white;">Install CRMAIze</h4>
            <p style="margin: 0.5rem 0 0 0; font-size: 0.9rem; opacity: 0.9;">
              Add to your home screen for quick access and offline features
            </p>
          </div>
          <button class="button primary" onclick="crmaizePWA.installApp()" style="margin: 0;">
            Install
          </button>
        </div>
      </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', promptHtml);
    
    setTimeout(() => {
      const prompt = document.getElementById('installPrompt');
      if (prompt) prompt.classList.add('show');
    }, 2000); // Show after 2 seconds
  }
  
  // Install the PWA
  async installApp() {
    if (!this.deferredPrompt) return;
    
    try {
      const result = await this.deferredPrompt.prompt();
      console.log('[PWA] Install prompt result:', result);
      
      if (result.outcome === 'accepted') {
        console.log('[PWA] User accepted install');
      } else {
        console.log('[PWA] User dismissed install');
      }
      
      this.deferredPrompt = null;
      this.hideInstallPrompt();
    } catch (error) {
      console.error('[PWA] Install failed:', error);
    }
  }
  
  // Hide install prompt
  hideInstallPrompt() {
    const prompt = document.getElementById('installPrompt');
    if (prompt) {
      prompt.remove();
    }
  }
  
  // Show install success message
  showInstallSuccess() {
    const successHtml = `
      <div class="pwa-install-prompt show" id="installSuccess" style="background: #28a745;">
        <div style="display: flex; align-items: center; gap: 1rem;">
          <div style="font-size: 2rem;">✅</div>
          <div>
            <h4 style="margin: 0; color: white;">Successfully Installed!</h4>
            <p style="margin: 0.5rem 0 0 0; font-size: 0.9rem; opacity: 0.9;">
              CRMAIze is now available on your home screen
            </p>
          </div>
        </div>
      </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', successHtml);
    
    setTimeout(() => {
      const success = document.getElementById('installSuccess');
      if (success) success.remove();
    }, 5000);
  }
  
  // Setup mobile-specific features
  setupMobileFeatures() {
    // Prevent zoom on input focus (iOS)
    if (/iPad|iPhone|iPod/.test(navigator.userAgent)) {
      const inputs = document.querySelectorAll('input, select, textarea');
      inputs.forEach(input => {
        if (input.style.fontSize < '16px') {
          input.style.fontSize = '16px';
        }
      });
    }
    
    // Add touch-friendly classes
    document.body.classList.add('touch-device');
    
    // Improve button accessibility
    const buttons = document.querySelectorAll('.button, button');
    buttons.forEach(button => {
      button.classList.add('touch-friendly');
    });
    
    // Handle viewport height on mobile (address bar)
    this.handleViewportHeight();
    window.addEventListener('resize', () => this.handleViewportHeight());
  }
  
  // Handle mobile viewport height issues
  handleViewportHeight() {
    const vh = window.innerHeight * 0.01;
    document.documentElement.style.setProperty('--vh', `${vh}px`);
  }
  
  // Setup offline detection
  setupOfflineDetection() {
    const showOfflineIndicator = () => {
      let indicator = document.getElementById('offlineIndicator');
      if (!indicator) {
        indicator = document.createElement('div');
        indicator.id = 'offlineIndicator';
        indicator.className = 'offline-indicator';
        indicator.innerHTML = '📡 You are offline - Some features may be limited';
        document.body.appendChild(indicator);
      }
      indicator.classList.add('show');
    };
    
    const hideOfflineIndicator = () => {
      const indicator = document.getElementById('offlineIndicator');
      if (indicator) {
        indicator.classList.remove('show');
        setTimeout(() => indicator.remove(), 300);
      }
    };
    
    window.addEventListener('offline', showOfflineIndicator);
    window.addEventListener('online', hideOfflineIndicator);
    
    // Initial check
    if (!navigator.onLine) {
      showOfflineIndicator();
    }
  }
  
  // Setup touch gestures
  setupTouchGestures() {
    let startX, startY, startTime;
    
    document.addEventListener('touchstart', event => {
      const touch = event.touches[0];
      startX = touch.clientX;
      startY = touch.clientY;
      startTime = Date.now();
    }, { passive: true });
    
    document.addEventListener('touchend', event => {
      if (!startX || !startY) return;
      
      const touch = event.changedTouches[0];
      const endX = touch.clientX;
      const endY = touch.clientY;
      const endTime = Date.now();
      
      const deltaX = endX - startX;
      const deltaY = endY - startY;
      const deltaTime = endTime - startTime;
      
      // Swipe detection
      const minSwipeDistance = 50;
      const maxSwipeTime = 300;
      
      if (Math.abs(deltaX) > minSwipeDistance && deltaTime < maxSwipeTime) {
        if (deltaX > 0) {
          this.handleSwipeRight();
        } else {
          this.handleSwipeLeft();
        }
      }
      
      // Reset
      startX = startY = null;
    }, { passive: true });
  }
  
  // Handle swipe gestures
  handleSwipeRight() {
    // Open sidebar if available
    const offCanvasToggle = document.querySelector('[data-toggle="offCanvas"]');
    if (offCanvasToggle && window.innerWidth <= 768) {
      offCanvasToggle.click();
    }
  }
  
  handleSwipeLeft() {
    // Close sidebar if open
    const offCanvas = document.getElementById('offCanvas');
    if (offCanvas && offCanvas.classList.contains('is-open')) {
      offCanvas.classList.remove('is-open');
    }
  }
  
  // Setup keyboard shortcuts
  setupKeyboardShortcuts() {
    document.addEventListener('keydown', event => {
      // Ctrl/Cmd + K for search (future feature)
      if ((event.ctrlKey || event.metaKey) && event.key === 'k') {
        event.preventDefault();
        console.log('[PWA] Search shortcut triggered');
      }
      
      // Alt + D for dashboard
      if (event.altKey && event.key === 'd') {
        event.preventDefault();
        window.location.href = '/dashboard';
      }
      
      // Alt + C for campaigns
      if (event.altKey && event.key === 'c') {
        event.preventDefault();
        window.location.href = '/campaigns';
      }
      
      // Alt + A for analytics
      if (event.altKey && event.key === 'a') {
        event.preventDefault();
        window.location.href = '/analytics';
      }
    });
  }
  
  // Handle service worker messages
  handleServiceWorkerMessage(event) {
    const { data } = event;
    
    switch (data.type) {
      case 'UPDATE_AVAILABLE':
        this.showUpdateAvailable();
        break;
      case 'CACHE_UPDATED':
        console.log('[PWA] Cache updated');
        break;
      default:
        console.log('[PWA] Service worker message:', data);
    }
  }
  
  // Show update available notification
  showUpdateAvailable() {
    const updateHtml = `
      <div class="pwa-install-prompt show" id="updatePrompt" style="background: #ffc107; color: #212529;">
        <div style="display: flex; align-items: center; gap: 1rem;">
          <div style="font-size: 2rem;">🔄</div>
          <div style="flex: 1;">
            <h4 style="margin: 0; color: #212529;">Update Available</h4>
            <p style="margin: 0.5rem 0 0 0; font-size: 0.9rem; opacity: 0.8;">
              A new version of CRMAIze is ready to install
            </p>
          </div>
          <button class="button" onclick="crmaizePWA.updateApp()" style="margin: 0; background: #212529; color: white;">
            Update
          </button>
        </div>
      </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', updateHtml);
    
    setTimeout(() => {
      const prompt = document.getElementById('updatePrompt');
      if (prompt) prompt.remove();
    }, 10000); // Auto-hide after 10 seconds
  }
  
  // Update the app
  updateApp() {
    if ('serviceWorker' in navigator) {
      navigator.serviceWorker.getRegistration().then(registration => {
        if (registration && registration.waiting) {
          registration.waiting.postMessage({ type: 'SKIP_WAITING' });
          window.location.reload();
        }
      });
    }
    
    const prompt = document.getElementById('updatePrompt');
    if (prompt) prompt.remove();
  }
  
  // Background sync for offline actions
  async syncWhenOnline(tag, data) {
    if ('serviceWorker' in navigator && 'sync' in window.ServiceWorkerRegistration.prototype) {
      try {
        const registration = await navigator.serviceWorker.ready;
        
        // Store data for sync
        localStorage.setItem(`sync-${tag}`, JSON.stringify(data));
        
        // Register background sync
        await registration.sync.register(tag);
        console.log('[PWA] Background sync registered:', tag);
      } catch (error) {
        console.error('[PWA] Background sync failed:', error);
      }
    }
  }
  
  // Share API integration
  async share(data) {
    if (navigator.share) {
      try {
        await navigator.share(data);
        console.log('[PWA] Content shared successfully');
      } catch (error) {
        console.log('[PWA] Share cancelled or failed:', error);
        this.fallbackShare(data);
      }
    } else {
      this.fallbackShare(data);
    }
  }
  
  // Fallback share method
  fallbackShare(data) {
    if (navigator.clipboard) {
      navigator.clipboard.writeText(data.url || data.text || '').then(() => {
        this.showToast('Link copied to clipboard!');
      });
    }
  }
  
  // Show toast notification
  showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    toast.style.cssText = `
      position: fixed;
      bottom: 20px;
      left: 50%;
      transform: translateX(-50%);
      background: #333;
      color: white;
      padding: 0.8rem 1.5rem;
      border-radius: 25px;
      z-index: 1002;
      animation: toastSlide 0.3s ease-out;
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
      toast.style.animation = 'toastSlide 0.3s ease-out reverse';
      setTimeout(() => toast.remove(), 300);
    }, 3000);
  }
}

// Initialize PWA functionality
const crmaizePWA = new CRMAIzePWA();

// Export for global access
window.crmaizePWA = crmaizePWA;

// Add toast animation styles
if (!document.getElementById('pwa-styles')) {
  const style = document.createElement('style');
  style.id = 'pwa-styles';
  style.textContent = `
    @keyframes toastSlide {
      from {
        transform: translateX(-50%) translateY(100%);
        opacity: 0;
      }
      to {
        transform: translateX(-50%) translateY(0);
        opacity: 1;
      }
    }
    
    .pwa-standalone {
      padding-top: env(safe-area-inset-top);
      padding-bottom: env(safe-area-inset-bottom);
    }
    
    .touch-device .button:hover {
      transform: scale(0.98);
    }
    
    .touch-device .button:active {
      transform: scale(0.95);
    }
  `;
  document.head.appendChild(style);
}