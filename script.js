// Smooth scrolling for navigation links
document.addEventListener('DOMContentLoaded', function() {
    const navLinks = document.querySelectorAll('.nav-links a[href^="#"]');
    
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            const targetId = this.getAttribute('href');
            const targetSection = document.querySelector(targetId);
            
            if (targetSection) {
                targetSection.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
});

// Navbar background on scroll
window.addEventListener('scroll', function() {
    const navbar = document.querySelector('.navbar');
    if (window.scrollY > 50) {
        navbar.style.background = 'rgba(255, 255, 255, 0.98)';
        navbar.style.boxShadow = '0 2px 20px rgba(0, 0, 0, 0.1)';
    } else {
        navbar.style.background = 'rgba(255, 255, 255, 0.95)';
        navbar.style.boxShadow = 'none';
    }
});

// Waitlist form functionality
function joinWaitlist() {
    const emailInput = document.getElementById('email-input');
    const email = emailInput.value.trim();
    
    if (!email) {
        showNotification('Please enter your email address', 'error');
        return;
    }
    
    if (!isValidEmail(email)) {
        showNotification('Please enter a valid email address', 'error');
        return;
    }
    
    // Simulate API call
    const submitBtn = document.querySelector('.submit-btn');
    const originalText = submitBtn.textContent;
    
    submitBtn.textContent = 'Joining...';
    submitBtn.disabled = true;
    
    setTimeout(() => {
        submitBtn.textContent = '✓ Joined!';
        emailInput.value = '';
        showNotification('Welcome to the FlyerCatcher waitlist! We\'ll keep you updated on our progress.', 'success');
        
        setTimeout(() => {
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
        }, 3000);
    }, 1500);
}

// Email validation
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Notification system
function showNotification(message, type = 'info') {
    // Remove existing notification
    const existingNotification = document.querySelector('.notification');
    if (existingNotification) {
        existingNotification.remove();
    }
    
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <span>${message}</span>
            <button onclick="this.parentElement.parentElement.remove()">×</button>
        </div>
    `;
    
    // Add notification styles
    notification.style.cssText = `
        position: fixed;
        top: 90px;
        right: 20px;
        background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#6366f1'};
        color: white;
        padding: 15px 20px;
        border-radius: 10px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        z-index: 1001;
        max-width: 400px;
        transform: translateX(100%);
        transition: transform 0.3s ease;
    `;
    
    notification.querySelector('.notification-content').style.cssText = `
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
    `;
    
    notification.querySelector('button').style.cssText = `
        background: none;
        border: none;
        color: white;
        font-size: 18px;
        cursor: pointer;
        padding: 0;
        width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
    `;
    
    document.body.appendChild(notification);
    
    // Animate in
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentElement) {
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 300);
        }
    }, 5000);
}

// Handle Enter key in email input
document.addEventListener('DOMContentLoaded', function() {
    const emailInput = document.getElementById('email-input');
    if (emailInput) {
        emailInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                joinWaitlist();
            }
        });
    }
});

// Animate elements on scroll
function animateOnScroll() {
    const elements = document.querySelectorAll('.feature-card, .vision-item');
    
    elements.forEach(element => {
        const elementTop = element.getBoundingClientRect().top;
        const elementVisible = 150;
        
        if (elementTop < window.innerHeight - elementVisible) {
            element.style.opacity = '1';
            element.style.transform = 'translateY(0)';
        }
    });
}

// Initialize scroll animations
document.addEventListener('DOMContentLoaded', function() {
    const elements = document.querySelectorAll('.feature-card, .vision-item');
    elements.forEach(element => {
        element.style.opacity = '0';
        element.style.transform = 'translateY(30px)';
        element.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
    });
    
    window.addEventListener('scroll', animateOnScroll);
    animateOnScroll(); // Check initial state
});

// Mobile menu toggle (for future enhancement)
function toggleMobileMenu() {
    const navLinks = document.querySelector('.nav-links');
    navLinks.classList.toggle('mobile-active');
}

// CTA button interactions
document.addEventListener('DOMContentLoaded', function() {
    const ctaButtons = document.querySelectorAll('.cta-btn, .primary-btn, .secondary-btn');
    
    ctaButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (this.textContent.includes('Join Waitlist') || this.textContent.includes('Get Early Access')) {
                e.preventDefault();
                document.querySelector('#email-input').focus();
                document.querySelector('#email-input').scrollIntoView({ behavior: 'smooth' });
            } else if (this.textContent.includes('Try Prototype')) {
                e.preventDefault();
                window.open('bulletin-board.html', '_blank');
            }
        });
    });
});

// Add loading animation for prototype button
document.addEventListener('DOMContentLoaded', function() {
    const prototypeBtn = document.querySelector('.prototype-btn');
    if (prototypeBtn) {
        prototypeBtn.addEventListener('click', function() {
            const originalContent = this.innerHTML;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading Prototype...';
            
            setTimeout(() => {
                this.innerHTML = originalContent;
            }, 2000);
        });
    }
});

// Easter egg - Konami code
let konamiCode = [];
const konamiSequence = [38, 38, 40, 40, 37, 39, 37, 39, 66, 65]; // ↑↑↓↓←→←→BA

document.addEventListener('keydown', function(e) {
    konamiCode.push(e.keyCode);
    
    if (konamiCode.length > konamiSequence.length) {
        konamiCode.shift();
    }
    
    if (konamiCode.length === konamiSequence.length && 
        konamiCode.every((code, index) => code === konamiSequence[index])) {
        
        showNotification('🎉 You found the easter egg! Welcome to the FlyerCatcher dev team!', 'success');
        
        // Add some fun animation
        document.body.style.animation = 'rainbow 2s infinite';
        setTimeout(() => {
            document.body.style.animation = '';
        }, 4000);
    }
});

// Add rainbow animation CSS
const style = document.createElement('style');
style.textContent = `
    @keyframes rainbow {
        0% { filter: hue-rotate(0deg); }
        100% { filter: hue-rotate(360deg); }
    }
`;
document.head.appendChild(style);

// Fetch and display events on load
async function fetchEvents(category = 'all') {
    const url = category === 'all' ? '/api.php?path=events' : `/api.php?path=events&category=${category.toLowerCase().replace(' & ', '_')}`;
    const response = await fetch(url);
    const events = await response.json();
    const grid = document.getElementById('event-grid');
    grid.innerHTML = ''; // Clear grid
    events.forEach(event => {
        const card = document.createElement('div');
        card.className = 'event-card'; // Add styling in CSS
        card.innerHTML = `
            <h3>${event.title}</h3>
            <p>${event.description}</p>
            <p>Location: ${event.location}</p>
            <p>Date: ${event.formatted_date}</p>
            <p>Posted: ${event.posted_date}</p>
            ${event.image_url ? `<img src="${event.image_url}" alt="Event Image">` : `<div style="background: ${event.image_gradient}; height: 200px;"></div>`}
        `;
        grid.appendChild(card);
    });
}

// Handle form submission
document.getElementById('shareEventBtn').addEventListener('click', async () => { // Assume button ID
    const formData = new FormData();
    formData.append('title', document.getElementById('eventTitle').value);
    formData.append('description', document.getElementById('eventDescription').value);
    formData.append('location', document.getElementById('eventLocation').value);
    let eventDate = document.getElementById('eventDate').value;
    eventDate = eventDate.replace('T', ' ') + ':00'; // Fix format
    formData.append('event_date', eventDate);
    formData.append('category', document.getElementById('eventCategory').value.toLowerCase().replace(' & ', '_'));
    const file = document.getElementById('eventImage').files[0];
    if (file) formData.append('image', file);

    const response = await fetch('/api.php?path=events', { method: 'POST', body: formData });
    if (response.ok) {
        fetchEvents(); // Refresh grid
        document.getElementById('shareEventModal').classList.add('hidden'); // Close modal
    } else {
        alert('Error adding event');
    }
});

// Filter buttons
document.querySelectorAll('.filter-btn').forEach(btn => { // Assume class on filters
    btn.addEventListener('click', () => fetchEvents(btn.textContent.trim()));
});

// Initial load
window.addEventListener('load', () => fetchEvents());
