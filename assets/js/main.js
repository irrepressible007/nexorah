// Main JavaScript file for Nexorah website

// Form validation for contact form
const contactForm = document.getElementById('contactForm');
if (contactForm) {
    contactForm.addEventListener('submit', function(e) {
        let isValid = true;
        const nameInput = document.getElementById('name');
        const emailInput = document.getElementById('email');
        const messageInput = document.getElementById('message');
        
        // Reset error messages
        document.querySelectorAll('.invalid-feedback').forEach(el => el.style.display = 'none');
        document.querySelectorAll('.form-control').forEach(el => el.classList.remove('is-invalid'));
        
        // Validate name
        if (nameInput.value.trim() === '') {
            document.getElementById('nameError').style.display = 'block';
            nameInput.classList.add('is-invalid');
            isValid = false;
        }
        
        // Validate email
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(emailInput.value.trim())) {
            document.getElementById('emailError').style.display = 'block';
            emailInput.classList.add('is-invalid');
            isValid = false;
        }
        
        // Validate message
        if (messageInput.value.trim() === '') {
            document.getElementById('messageError').style.display = 'block';
            messageInput.classList.add('is-invalid');
            isValid = false;
        }
        
        if (!isValid) {
            e.preventDefault();
        }
    });
}

// Image preview for file uploads in admin panel
const fileInputs = document.querySelectorAll('.custom-file-input');
fileInputs.forEach(input => {
    input.addEventListener('change', function() {
        const fileName = this.files[0].name;
        const label = this.nextElementSibling;
        label.textContent = fileName;
        
        // Preview image if it's an image file
        const previewId = this.getAttribute('data-preview');
        if (previewId && this.files && this.files[0]) {
            const reader = new FileReader();
            const preview = document.getElementById(previewId);
            
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
            
            reader.readAsDataURL(this.files[0]);
        }
    });
});

// Confirm delete action
const deleteButtons = document.querySelectorAll('.delete-btn');
deleteButtons.forEach(button => {
    button.addEventListener('click', function(e) {
        if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
            e.preventDefault();
        }
    });
});

// Toggle video/image upload form in admin panel
const mediaTypeSelect = document.getElementById('mediaType');
if (mediaTypeSelect) {
    const videoUpload = document.getElementById('videoUpload');
    const imageUpload = document.getElementById('imageUpload');
    
    mediaTypeSelect.addEventListener('change', function() {
        if (this.value === 'video') {
            videoUpload.style.display = 'block';
            imageUpload.style.display = 'none';
        } else {
            videoUpload.style.display = 'none';
            imageUpload.style.display = 'block';
        }
    });
}