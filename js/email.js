// Initialisation d'EmailJS
(function() {
    emailjs.init("Tak7UzRffAV6wVZ2v");
})();

// Fonction d'envoi d'email
async function sendEmail(formData) {
    try {
        // Vérification des données avant envoi
        const name = formData.get('name')?.trim();
        const email = formData.get('email')?.trim();
        const subject = formData.get('subject')?.trim();
        const message = formData.get('msg')?.trim();

        // Validation des champs
        if (!name || !email || !subject || !message) {
            throw new Error('Tous les champs sont requis');
        }

        // Validation basique de l'email
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            throw new Error('Format d\'email invalide');
        }

        const templateParams = {
            from_name: name,
            from_email: email,
            user_email: "compaoreh338@gmail.com",
            to_name: "Hyacinthe Compaore",
            subject: subject,
            message: message
        };

        const response = await emailjs.send(
            "service_6bgheul",
            "template_dji2eac",
            templateParams
        );

        if (response.status === 200) {
            return { success: true, message: 'Message envoyé avec succès' };
        } else {
            throw new Error('Erreur lors de l\'envoi du message');
        }
    } catch (error) {
        console.error('Erreur:', error.message);
        throw error;
    }
}

// Gestion du formulaire
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('contactForm');
    const messageDiv = document.createElement('div');
    messageDiv.className = 'st-form-message';
    form.appendChild(messageDiv);

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Vérification du honeypot
        const honeypot = form.querySelector('input[name="website"]');
        if (honeypot.value) {
            return; // C'est probablement un bot, on ne fait rien
        }

        try {
            messageDiv.className = 'st-form-message';
            messageDiv.textContent = 'Envoi en cours...';
            
            const formData = new FormData(form);
            await sendEmail(formData);
            
            messageDiv.className = 'st-form-message success';
            messageDiv.textContent = 'Message envoyé avec succès !';
            form.reset();
        } catch (error) {
            messageDiv.className = 'st-form-message error';
            messageDiv.textContent = error.message || 'Une erreur est survenue';
        }
    });
}); 