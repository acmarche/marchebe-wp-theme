<?php

namespace AcMarche\Theme\Templates;


use AcMarche\Theme\Inc\RouterBottin;

get_header();

$slug = get_query_var(RouterBottin::PARAM_CATEGORY, null);
dd($slug);

get_footer();