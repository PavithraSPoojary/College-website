document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuBtn = document.querySelector('.mobile-menu');
    const navMenu = document.querySelector('nav ul');
    const header = document.querySelector('header');
    const dropdown = document.querySelector('.dropdown');

    // Toggle mobile menu
    function toggleMenu() {
        navMenu.classList.toggle('active');
        
        // Toggle icon and header height
        mobileMenuBtn.innerHTML = navMenu.classList.contains('active') ? '✕' : '☰';
        header.style.height = navMenu.classList.contains('active') ? 'auto' : '';
    }

    // Mobile menu button click
    mobileMenuBtn.addEventListener('click', toggleMenu);

    // Close menu when nav link is clicked (mobile only)
    const navLinks = document.querySelectorAll('nav ul li a:not(.dropdown > a)');
    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                navMenu.classList.remove('active');
                mobileMenuBtn.innerHTML = '☰';
                header.style.height = '';
            }
        });
    });

    // Dropdown functionality
    if (dropdown) {
        const dropdownMenu = dropdown.querySelector('.dropdown-menu');
        
        // Desktop hover
        dropdown.addEventListener('mouseenter', function() {
            if (window.innerWidth > 768) {
                dropdownMenu.style.display = 'block';
            }
        });
        
        dropdown.addEventListener('mouseleave', function() {
            if (window.innerWidth > 768) {
                dropdownMenu.style.display = 'none';
            }
        });
        
        // Mobile touch
        dropdown.addEventListener('click', function(e) {
            if (window.innerWidth <= 768) {
                e.preventDefault();
                dropdownMenu.style.display = dropdownMenu.style.display === 'block' ? 'none' : 'block';
            }
        });
    }

    // Window resize handler
    function handleResize() {
        if (window.innerWidth > 768) {
            navMenu.classList.remove('active');
            mobileMenuBtn.innerHTML = '☰';
            header.style.height = '';
            
            // Reset dropdown menu if exists
            if (dropdown) {
                dropdown.querySelector('.dropdown-menu').style.display = 'none';
            }
        }
    }
    
    window.addEventListener('resize', handleResize);
});
// ===========responsive related code=============

// Update your animateMenu function to this:
const animateMenu = () => {
    if (state.isMenuOpen) {
        dom.navMenu.style.display = 'flex'; // Ensure display is set first
        // Trigger reflow before animation
        void dom.navMenu.offsetHeight;
        dom.navMenu.style.transform = 'translateY(0)';
        dom.navMenu.style.opacity = '1';
    } else {
        dom.navMenu.style.transform = 'translateY(-20px)';
        dom.navMenu.style.opacity = '0';
        // Wait for animation to complete before hiding
        setTimeout(() => {
            if (!state.isMenuOpen) {
                dom.navMenu.style.display = 'none';
            }
        }, 300);
    }
};