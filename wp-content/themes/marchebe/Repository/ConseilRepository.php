<?php

namespace AcMarche\Theme\Repository;

use Symfony\Component\Finder\Finder;

class ConseilRepository
{
    public function getAllOrdre(): array
    {
        global $wpdb;

        $results = $wpdb->get_results(
            'SELECT * FROM conseil.ordre_jour ORDER BY `date_ordre` DESC',
            OBJECT
        );

        return $results ?: [];
    }

    /**
     * 2019 => 2023
     * @return array
     */
    public function findFromDb(): array
    {
        global $wpdb;

        $results = $wpdb->get_results(
            'SELECT * FROM conseil.pv ORDER BY `createdAt` DESC',
            ARRAY_A
        );

        if (!$results) {
            return [];
        }

        return array_map(function (array $pv) {
            $pv['url'] = '/wp-content/uploads/conseil/pv/'.$pv['file_name'];
            $pv['year'] = null;
            try {
                $date_time = new \DateTime($pv['date_pv']);
                $pv['year'] = $date_time->format('Y');
            } catch (\Exception) {
                $pv['year'] = null;
            }

            return $pv;
        }, $results);
    }

    function findFromDirectory(): array
    {
        $files = array();
        foreach (range(2019, 2013) as $year) {
            $path = '/uploads/conseil/pv/'.$year.'/';
            $dir = WP_CONTENT_DIR.$path;

            if (!is_dir($dir)) {
                return $files;
            }

            $finder = new Finder();
            $finder->files()->in($dir);
            $finder->sort(
                fn($a, $b) => ($a->getrelativePathname() > $b->getrelativePathname()) ? -1 : 1
            );

            foreach ($finder as $file) {
                $data = [];
                $fileName = $file->getRelativePathname();
                $data['nom'] = $fileName;
                $data['year'] = $year;
                $data['url'] = '/wp-content'.$path.$fileName;
                $file_info = pathinfo($dir.$file);
                $fichier = $file_info['filename'];

                try {
                    $date_time = new \DateTime($fichier);
                    $data['date_pv'] = $date_time->format("Y-m-d");
                } catch (\Exception) {
                    $data['date_pv'] = $fileName;
                }

                $files[] = $data;
            }
        }

        return $files;
    }
}