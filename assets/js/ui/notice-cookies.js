import Orejime from 'orejime/dist/orejime';

Orejime.init({
    privacyPolicy: "http://ods-beta.tela-botanica.org/mentions-legales",
    apps: [
        {
            name: "google-tag-manager",
            title: "Google Tag Manager",
            purposes: ["analytics"],
            cookies: [
                "_ga",
                "_gat",
                "_gid",
                "__utma",
                "__utmb",
                "__utmc",
                "__utmt",
                "__utmz",
                "_gat_gtag_" + GTM_UA,
                "_gat_" + GTM_UA
            ],
        }
    ],
    purposes: ["analytics"],
    translations: {
        fr: {
            purposes: {
                analytics: "Statistiques d'audience",
            }
        }
    }
});
