<?php

namespace AcMarche\Theme;

use AcMarche\Theme\Lib\Twig;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

$twig = Twig::loadTwig();
try {
    echo $twig->render('@AcMarche/_footer.html.twig', [

    ]);
} catch (LoaderError|RuntimeError|SyntaxError $e) {
    echo $e->getMessage();
}
wp_footer();
?>
    </body>
    </html>
<?php