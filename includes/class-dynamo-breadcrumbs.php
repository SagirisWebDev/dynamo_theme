<?php
declare(strict_types=1);

class Dynamo_Breadcrumbs {

    public static function render(): void {
        if (!Dynamo_Options::is_feature_enabled('breadcrumbs')) {
            return;
        }
        if (is_front_page()) {
            return;
        }

        $trail = self::build_trail();
        if (empty($trail)) {
            return;
        }

        echo '<nav class="dynamo-breadcrumbs" aria-label="' . esc_attr__('Breadcrumb', 'dynamo') . '"><ol>';
        $last = count($trail) - 1;
        foreach ($trail as $i => $crumb) {
            echo '<li>';
            if ($i < $last && !empty($crumb['url'])) {
                echo '<a href="' . esc_url($crumb['url']) . '">' . esc_html($crumb['label']) . '</a>';
            } else {
                echo '<span aria-current="page">' . esc_html($crumb['label']) . '</span>';
            }
            echo '</li>';
        }
        echo '</ol></nav>';
    }

    private static function build_trail(): array {
        $trail = [
            ['label' => __('Home', 'dynamo'), 'url' => home_url('/')],
        ];

        if (is_singular('post')) {
            $cats = get_the_category();
            if (!empty($cats)) {
                $cat = $cats[0];
                $trail[] = ['label' => $cat->name, 'url' => get_category_link($cat->term_id)];
            }
            $trail[] = ['label' => get_the_title(), 'url' => ''];
        } elseif (is_page()) {
            $ancestors = array_reverse(get_post_ancestors(get_the_ID()));
            foreach ($ancestors as $ancestor_id) {
                $trail[] = ['label' => get_the_title($ancestor_id), 'url' => get_permalink($ancestor_id)];
            }
            $trail[] = ['label' => get_the_title(), 'url' => ''];
        } elseif (is_singular()) {
            $trail[] = ['label' => get_the_title(), 'url' => ''];
        } elseif (is_category() || is_tag() || is_tax()) {
            $trail[] = ['label' => single_term_title('', false), 'url' => ''];
        } elseif (is_author()) {
            $trail[] = ['label' => get_the_author(), 'url' => ''];
        } elseif (is_search()) {
            $trail[] = [
                'label' => sprintf(__('Search results for "%s"', 'dynamo'), get_search_query()),
                'url'   => '',
            ];
        } elseif (is_404()) {
            $trail[] = ['label' => __('Not found', 'dynamo'), 'url' => ''];
        } elseif (is_archive()) {
            $trail[] = ['label' => get_the_archive_title(), 'url' => ''];
        } else {
            return [];
        }

        return $trail;
    }
}
