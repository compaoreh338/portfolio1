// Initialisation d'EmailJS
(function() {
    emailjs.init("Tak7UzRffAV6wVZ2v");
})();

// Fonction d'envoi d'email
async function sendEmail(formData) {
    try {
        // Vérification des données avant envoi
        const name = formData.get('name');
        const email = formData.get('email');
        const subject = formData.get('subject');
        const message = formData.get('msg');

        console.log('Données du formulaire:', { name, email, subject, message });

        if (!name || !email || !subject || !message) {
            throw new Error('Tous les champs sont requis');
        }

        console.log('Envoi avec les paramètres:', { name, email, subject, message });

        const templateParams = {
            from_name: name,
            from_email: email,
            user_email: "compaoreh338@gmail.com", // Votre adresse email
            to_name: "Hyacinthe Compaore", // Votre nom
            subject: subject,
            message: message
        };

        console.log('Envoi avec les paramètres:', templateParams);

        const response = await emailjs.send(
            "service_6bgheul",
            "template_dji2eac",
            templateParams
        );

        console.log('Réponse EmailJS:', response);
        return { success: true, message: 'Message envoyé avec succès!' };
    } catch (error) {
        console.error('Erreur détaillée EmailJS:', {
            message: error.message,
            text: error.text,
            status: error.status,
            stack: error.stack
        });
        
        // Message d'erreur plus spécifique
        let errorMessage = 'Erreur lors de l\'envoi du message. ';
        if (error.text) {
            errorMessage += error.text;
        } else if (error.message) {
            errorMessage += error.message;
        } else {
            errorMessage += 'Veuillez réessayer.';
        }
        
        return { success: false, message: errorMessage };
    }
}

// Gestion du formulaire
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('contactForm');
    if (!form) {
        console.error('Formulaire non trouvé');
        return;
    }

    const submitButton = form.querySelector('button[type="submit"]');
    const formMessage = document.createElement('div');
    formMessage.className = 'st-form-message';
    form.appendChild(formMessage);

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        console.log('Soumission du formulaire');

        // Vérification du honeypot
        const honeypot = form.querySelector('input[name="website"]');
        if (honeypot.value) {
            console.log('Spam détecté');
            return;
        }

        // Désactiver le bouton pendant l'envoi
        submitButton.disabled = true;
        submitButton.innerHTML = 'Envoi en cours...';

        try {
            const formData = new FormData(form);
            console.log('FormData créé');
            
            const result = await sendEmail(formData);
            console.log('Résultat de l\'envoi:', result);

            // Afficher le message
            formMessage.className = 'st-form-message ' + (result.success ? 'success' : 'error');
            formMessage.textContent = result.message;

            // Réinitialiser le formulaire si succès
            if (result.success) {
                form.reset();
            }
        } catch (error) {
            console.error('Erreur lors de la soumission:', error);
            formMessage.className = 'st-form-message error';
            formMessage.textContent = 'Une erreur inattendue est survenue. Veuillez réessayer.';
        } finally {
            // Réactiver le bouton
            submitButton.disabled = false;
            submitButton.innerHTML = 'Envoyer';
        }
    });
}); 