<?php

namespace Cygnus\DrushExport\Informa;

/**
 * Provides support for exporting from Drupal 7.5x
 *
 * Needs working (as in you can login to admin) site for drush to work
 * from drush repo root: php -d phar.readonly=0 compile
 * from drupal site root (ie /sites/heathcare-informatics/): drush scr $pathToPhar $mongoIp $importConfigKey
 * ie: drush scr ~/environment/base-drush-export-master/build/export.phar [MONGO_IP] [IMPORT_KEY]
 *
 */
class BaseExport extends Export
{
    /**
     * {@inheritdoc}
     */
    protected $configs = [
        'industryweek'  => [
            'name'      => 'Industry Week',
            'database'  => 'drupal_informa_industryweek',
            'uri'       => 'https://www.industryweek.com',
            'Issue'     => [
                // 'magazine_covers' => 'Magazine\\Issue',                         // magazine cover image, digital edition url
            ],
            'Taxonomy'  => [
                // Shared/common
                // 'Company Type'              => 'Taxonomy\Bin',          // ~ 5 items
                // 'Source Type'               => 'Taxonomy\Bin',          // 5 items, used to denote UGC and/or display concerns
                // 'Subtype'                   => 'Taxonomy\Bin',          // 12 items, used to determine article sub type
                // 'Coverage Type'             => 'Taxonomy\Coverage',     // 20+ items, Similar to website sections?
                // 'Tags'                      => 'Taxonomy\Tag',
                // // AW-specific
                // 'App Platforms/OS'          => 'Taxonomy\Bin',          // ~5 items
                // 'Blog Beat'                 => 'Taxonomy\Bin',          // ~ 20 items
                // 'Column Type'               => 'Taxonomy\Bin',          // ~ 10 items
                // 'Download Subtype'          => 'Taxonomy\Bin',          // 2 items (Tactical Brief, Whitepaper)
                // 'Industry Type'             => 'Taxonomy\Bin',          // 4 items
                // 'Leadership Session'        => 'Taxonomy\Bin',          // 5 items, years 2015-2019
                // 'Automation Strategies'     => 'Taxonomy\Category',     // 100+ items
                // 'Topics'                    => 'Taxonomy\Topic',        // 11 items
                // // New hierarchical types
                // 'Industries'                => 'Taxonomy\Industries',   // 20+ items (top categories?)
                // 'Technologies'              => 'Taxonomy\Technology',   // ~50 items, similar to `Industries` taxonomy (if kept, create Technologies instead of Market)
            ],
            'Content'   => [
                'article'                       => 'Platform\\Content\\Article',
                //'block_content'               => 'Platform\\Content\\Article',
                //'media_entity'                => 'Platform\\Asset',
                //'page'                        => 'Platform\\Content\Page',
                'product'                       => 'Platform\\Content\\Product',
                //'page_table_detail'           => 'Platform\\Content\\Page',
                //'display_admin'               => 'Platform\\Content\\Page',
                //'gating_copy'                 => 'Platform\\Content\Whitepaper',
                //'store_front'                 => 'Platform\\Content\\Page'
            ],
            'Section'   => [
                // 'page' => 'Website\\Section',
                // 'page2' => 'Website\\Section',
            ],
            'structure' =>  [
                'stripFields'   => [
                    // These fields will be removed from nodes before being written to mongo
                    //"_id",
                    //"body",
                    "changed",
                    "cid",
                    "comment",
                    "comment_count",
                    //"created",
                    "data",
                    "entity_modified",
                    "field_bibblio_catalogueid",
                    "field_bibblio_contentitemid",
                    "field_informa_background_color",
                    "field_informa_background_image",
                    "field_informa_eloqua_embed_code",
                    "field_informa_research_pages",
                    "field_penton_aggregated_content",
                    "field_penton_allow_in_rss_feed",
                    "field_penton_article_type,field_penton_author",
                    "field_penton_buyers_journey",
                    "field_penton_byline",
                    "field_penton_content_summary",
                    "field_penton_content_team_goals",
                    "field_penton_errata_date",
                    "field_penton_errata_note",
                    "field_penton_google_news_flag",
                    "field_penton_inline_related",
                    "field_penton_is_published",
                    "field_penton_legacy_id",
                    "field_penton_link",
                    "field_penton_link_image_gallery",
                    "field_penton_link_media_entity",
                    "field_penton_link_media_feat_img",
                    "field_penton_native_advertising",
                    "field_penton_press_release_flag",
                    "field_penton_primary_category",
                    "field_penton_privacy_settings",
                    "field_penton_program",
                    "field_penton_promote_on_site",
                    "field_penton_publish_time",
                    "field_penton_publish_user",
                    "field_penton_published_datetime",
                    "field_penton_related_content",
                    "field_penton_secondary_category",
                    "field_penton_site_name",
                    "field_penton_source_id",
                    "field_penton_sponsor_name",
                    "field_penton_syndicate_atom",
                    "field_penton_target_entity_id",
                    "field_penton_target_entity_vid",
                    "field_penton_transaction_id",
                    "language",
                    "last_comment_name",
                    "last_comment_timestamp",
                    "last_comment_uid",
                    //"legacy",
                    "log",
                    "metatags",
                    //"mutations",
                    //"name",
                    "nid",
                    "path",
                    "picture",
                    "print_html_display",
                    "print_html_display_comment",
                    "print_html_display_urllist",
                    "promote",
                    //"published",
                    "revision_timestamp",
                    "revision_uid",
                    //"status",
                    "sticky",
                    "title",
                    "tnid",
                    "translate",
                    //"type",
                    "uid",
                    "updated",
                    "uuid",
                    "vid",
                    "vuuid"
                ]
            ],
        ],
    ];
}
