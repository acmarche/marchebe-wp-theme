<?php

namespace AcMarche\Theme\Data;

class Data
{
    //st loup,marche public
    public static array $eventsToSkip = ['EVT-01-0AVJ-324P', 'EVT-A0-008E-101W'];

    /**
     * Homepage: the six tasks residents come for. Plain text, no icons.
     * `link` is [type, blogId, id] (type: post|category), resolved by HomeLinks
     * so a renamed page keeps working; `url` is used as-is.
     */
    const tasks = [
        [
            'name' => 'Calendrier des collectes',
            'description' => 'Jours de ramassage, sacs PMC, recyparc',
            'link' => ['post', 1, 10463],
            'url' => '/environnement/dechets/calendrier-des-collectes-10463/',
        ],
        [
            'name' => 'Conseil communal',
            'description' => 'Prochaine séance, ordre du jour, PV',
            // The category page (/administration/le-conseil-communal/) returns a 500;
            // point at the agenda page until that is fixed.
            'link' => ['post', 2, 1650],
            'url' => '/administration/le-conseil-communal/ordre-du-jour-du-conseil-1650/',
        ],
        [
            'name' => 'Démarches en ligne (eGuichet)',
            'description' => 'Documents, commandes, rendez-vous',
            'url' => 'https://eguichet.marche.be/',
            'external' => true,
        ],
        [
            'name' => 'Horaires et contact',
            'description' => 'Administration, téléphone, rendez-vous',
            'link' => ['post', 2, 277],
            'url' => '/administration/contacter-la-commune/',
        ],
        [
            'name' => 'Travaux en cours',
            'description' => 'Chantiers et déviations',
            'url' => '/travaux-en-cours/',
        ],
        [
            'name' => 'Avis de publication',
            'description' => 'Enquêtes publiques, avis officiels',
            'link' => ['category', 2, 15],
            'url' => '/administration/?cat=15',
        ],
    ];

    /**
     * Homepage: secondary services, listed as text links below the news.
     */
    const services = [
        ['name' => 'CPAS', 'link' => ['category', 7, 23], 'url' => '/social/?cat=23'],
        ['name' => 'Avis de décès', 'link' => ['post', 1, 9635], 'url' => '/?p=9635'],
        ['name' => 'Emploi', 'link' => ['category', 1, 132], 'url' => '/?cat=132'],
        ['name' => 'Piscine', 'url' => 'https://rca-marche-en-famenne.be/web/be/page/piscine', 'external' => true],
        ['name' => 'Parking', 'link' => ['post', 1, 11139], 'url' => '/?p=11139'],
        ['name' => 'Économie (ADL)', 'url' => 'https://adl.marche.be', 'external' => true],
        ['name' => 'Volontariat', 'url' => 'https://volontariat.marche.be', 'external' => true],
        ['name' => 'Mon courriel @marche.be', 'url' => 'https://citoyen.marche.be/', 'external' => true],
    ];

    const partners =
        [
            [
                'url' => 'https://cap.marche.be',
                'img' => '/images/partners/img_sponsor02.webp',
                'width' => 341,
                'height' => 192,
                'alt' => 'Cap sur Marche',
                'id' => 1,
            ],
            [
                'url' => 'https://www.visitmarche.be',
                'img' => '/images/partners/visit_logo_b.webp',
                'width' => 337,
                'height' => 192,
                'alt' => 'Visit Marche',
                'id' => 2,
            ],
            [
                'url' => '/economie',
                'img' => '/images/partners/img_sponsor04.webp',
                'width' => 382,
                'height' => 192,
                'alt' => 'ADL Marche-en-Famenne',
                'id' => 3,
            ],
            [
                'url' => 'http://www.paysdefamenne.be',
                'img' => '/images/partners/img_sponsor03.webp',
                'width' => 288,
                'height' => 192,
                'alt' => 'Pays de Famenne',
                'id' => 4,
            ],
            [
                'url' => 'https://www.famenneardenne.be',
                'img' => '/images/partners/img_sponsor01.webp',
                'width' => 157,
                'height' => 176,
                'alt' => 'Famenne-Ardenne',
                'id' => 5,
            ],
            [
                'url' => 'https://mcfa.marche.be',
                'img' => '/images/partners/img_sponsor05.webp',
                'width' => 192,
                'height' => 192,
                'alt' => 'Maison de la Culture Famenne-Ardenne',
                'id' => 6,
            ],
            [
                'url' => 'https://artistes.marche.be',
                'img' => '/images/partners/artistes.webp',
                'width' => 214,
                'height' => 192,
                'alt' => 'Marche-en-Famenne, ville d’art et d’artistes',
                'id' => 7,
            ],
        ];

    const menuItems = [
        ['id' => 1, 'name' => 'eGuichet', 'link' => 'https://eguichet.marche.be'],
        ['id' => 2, 'name' => 'Actualités', 'link' => '/actualites'],
        ['id' => 3, 'name' => 'Horaires et contact', 'link' => '/administration/contacter-la-commune/'],
        ['id' => 4, 'name' => 'Agenda', 'link' => '/tourisme/agenda-des-manifestations'],
        ['id' => 5, 'name' => 'Rendez-vous', 'link' => 'https://marche.guichet-citoyen.be/demarches/'],
    ];
}