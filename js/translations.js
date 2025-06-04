const translations = {
    fr: {
        home: "Accueil",
        about: "À propos",
        contact: "Contact",
        hello: "Bonjour",
        aboutMe: "À propos",
        aboutMeSubtitle: "Qui suis-je ?",
        developer: "Développeur Full Stack",
        aboutText: "Je suis un développeur passionné avec une expertise en développement web et mobile. Mon objectif est de créer des applications performantes et intuitives qui répondent aux besoins des utilisateurs.",
        birthDate: "Date de naissance",
        phone: "Téléphone",
        email: "Email",
        address: "Adresse",
        languages: "Langues",
        available: "Disponible",
        downloadCV: "Télécharger CV",
        technologies_title: "Technologies",
        tools_title: "Outils",
        contactSubtitle: "Parlons de votre projet",
        name: "Votre nom",
        subject: "Sujet",
        message: "Votre message",
        sendMessage: "Envoyer",
        rights: "© 2024 Tous droits réservés",
        roles: [
            "Développeur Full Stack",
            "Développeur Web",
            "Développeur Mobile",
            "Développeur TYPO3",
            "Développeur Laravel"
        ],
        values: {
            birthDate: "19 septembre 2002",
            phone: "+226 73 13 19 66",
            email: "compaoreh338@gmail.com",
            address: "Ouagadougou",
            languages: "Français, anglais, mooré",
            available: "Disponible"
        }
    },
    en: {
        home: "Home",
        about: "About",
        contact: "Contact",
        hello: "Hello",
        aboutMe: "About Me",
        aboutMeSubtitle: "Who am I?",
        developer: "Full Stack Developer",
        aboutText: "I am a passionate developer with expertise in web and mobile development. My goal is to create performant and intuitive applications that meet user needs.",
        birthDate: "Birth Date",
        phone: "Phone",
        email: "Email",
        address: "Address",
        languages: "Languages",
        available: "Available",
        downloadCV: "Download CV",
        technologies_title: "Technologies",
        tools_title: "Tools",
        contactSubtitle: "Let's talk about your project",
        name: "Your name",
        subject: "Subject",
        message: "Your message",
        sendMessage: "Send",
        rights: "© 2024 All rights reserved",
        roles: [
            "Full Stack Developer",
            "Web Developer",
            "Mobile Developer",
            "TYPO3 Developer",
            "Laravel Developer"
        ],
        values: {
            birthDate: "September 19, 2002",
            phone: "+226 73 13 19 66",
            email: "compaoreh338@gmail.com",
            address: "Ouagadougou",
            languages: "French, English, Mooré",
            available: "Available"
        }
    }
};

// Variable globale pour Typed.js
let typed2;

// Fonction pour initialiser Typed.js
function initTyped(lang) {
    if (typed2) {
        typed2.destroy();
    }
    
    typed2 = new Typed('#typed2', {
        strings: translations[lang].roles,
        typeSpeed: 40,
        backSpeed: 40,
        startDelay: 1000,
        backDelay: 1500,
        loop: true,
        smartBackspace: true,
    });
}

// Fonction pour changer la langue
function changeLanguage(lang) {
    // Sauvegarder la préférence de langue
    localStorage.setItem('preferredLanguage', lang);
    
    // Mettre à jour la classe active sur les boutons de langue
    document.querySelectorAll('.st-lang-btn').forEach(btn => {
        btn.classList.remove('active');
        if (btn.getAttribute('data-lang') === lang) {
            btn.classList.add('active');
        }
    });

    // Mettre à jour tous les textes
    document.querySelectorAll('[data-translate]').forEach(element => {
        const key = element.getAttribute('data-translate');
        if (translations[lang][key]) {
            if (element.tagName === 'INPUT' || element.tagName === 'TEXTAREA') {
                element.placeholder = translations[lang][key];
            } else {
                element.textContent = translations[lang][key];
            }
        }
    });

    // Mettre à jour les valeurs des détails personnels
    const details = document.querySelectorAll('.st-text-block-details li span:last-child');
    details.forEach(detail => {
        const key = detail.getAttribute('data-value');
        if (key && translations[lang].values[key]) {
            detail.textContent = translations[lang].values[key];
        }
    });

    // Mettre à jour Typed.js
    initTyped(lang);
}

// Initialiser la langue au chargement
document.addEventListener('DOMContentLoaded', function() {
    // Ajouter les écouteurs d'événements pour les boutons de langue
    document.querySelectorAll('.st-lang-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const lang = this.getAttribute('data-lang');
            changeLanguage(lang);
        });
    });

    // Charger la langue sauvegardée ou utiliser le français par défaut
    const savedLang = localStorage.getItem('preferredLanguage') || 'fr';
    changeLanguage(savedLang);
}); 