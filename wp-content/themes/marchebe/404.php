<?php

namespace AcMarche\Theme\Templates;

use AcMarche\Theme\Lib\Twig;

get_header();

Twig::renderNotFoundPage('Page non trouvée');

get_footer();