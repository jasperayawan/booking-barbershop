/* Admin Dashboard JavaScript */

// Close modals
function closeModal(modalId) {
  document.getElementById(modalId).classList.remove('active');
}

// Close modal when clicking outside
document.querySelectorAll('.modal').forEach(modal => {
  modal.addEventListener('click', (e) => {
    if (e.target === modal) {
      modal.classList.remove('active');
    }
  });
});

// Notification system
function showNotification(message, type = 'success') {
  const notification = document.createElement('div');
  notification.style.cssText = `
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 16px 20px;
    border-radius: 12px;
    font-weight: 600;
    z-index: 2000;
    animation: slideIn 0.3s ease;
  `;

  if (type === 'success') {
    notification.style.background = '#10B981';
    notification.style.color = 'white';
  } else if (type === 'error') {
    notification.style.background = '#EF4444';
    notification.style.color = 'white';
  } else {
    notification.style.background = '#F59E0B';
    notification.style.color = 'white';
  }

  notification.textContent = message;
  document.body.appendChild(notification);

  setTimeout(() => {
    notification.style.animation = 'slideOut 0.3s ease';
    setTimeout(() => notification.remove(), 300);
  }, 3000);
}

// Add slide animations
const style = document.createElement('style');
style.textContent = `
  @keyframes slideIn {
    from {
      transform: translateX(400px);
      opacity: 0;
    }
    to {
      transform: translateX(0);
      opacity: 1;
    }
  }

  @keyframes slideOut {
    from {
      transform: translateX(0);
      opacity: 1;
    }
    to {
      transform: translateX(400px);
      opacity: 0;
    }
  }
`;
document.head.appendChild(style);

// Format currency
function formatCurrency(amount) {
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP'
  }).format(amount);
}

// Format date
function formatDate(dateStr) {
  return new Intl.DateTimeFormat('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  }).format(new Date(dateStr + 'T' + (dateStr.split(' ')[1] || '00:00')));
}

// Logout functionality
document.querySelectorAll('.logout-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    if (confirm('Are you sure you want to logout?')) {
      // TODO: Implement actual logout
      window.location.href = '../index.html';
    }
  });
});

console.log('Admin dashboard loaded');
