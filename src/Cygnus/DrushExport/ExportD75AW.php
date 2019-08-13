<?php

namespace Cygnus\DrushExport;

use DateTimeZone;
use DateTime;
use MongoDB\BSON\UTCDateTime;

/**
 * Provides support for exporting from Drupal 7.5x
 *
 * Needs working (as in you can login to admin) site for drush to work
 * from drush repo root: php -d phar.readonly=0 compile
 * from drupal site root (ie /sites/heathcare-informatics/): drush scr $pathToPhar $mongoIp $importConfigKey
 * ie: drush scr ~/environment/base-drush-export-master/build/export.phar [MONGO_IP] [IMPORT_KEY]
 *
 */
class ExportD75AW extends AbstractExport
{
    /**
     * {@inheritdoc}
     */
    protected $configs = [
        'aw'  => [
            'Taxonomy'  => [
                // Static lists (prefix with key aka `Column Type: Feed Forward`)
                'App Platforms/OS'          => 'Taxonomy\Bin',          // ~5 items
                'Blog Beat'                 => 'Taxonomy\Bin',          // ~ 20 items
                'Column Type'               => 'Taxonomy\Bin',          // ~ 10 items
                'Company Type'              => 'Taxonomy\Bin',          // ~ 5 items
                'Download Subtype'          => 'Taxonomy\Bin',          // 2 items (Tactical Brief, Whitepaper)
                'Industry Type'             => 'Taxonomy\Bin',          // 4 items
                'Leadership Session'        => 'Taxonomy\Bin',          // 5 items, years 2015-2019
                'Source Type'               => 'Taxonomy\Bin',          // 5 items, used to denote UGC and/or display concerns
                'Subtype'                   => 'Taxonomy\Bin',          // 12 items, used to determine article sub type

                // Tags
                'Tags'                      => 'Taxonomy\Tag',

                // Hierarchical
                'Automation Strategies'     => 'Taxonomy\Category',     // 100+ items
                'Topics'                    => 'Taxonomy\Topic',        // 11 items

                'Industries'                => 'Taxonomy\Industries',   // 20+ items (top categories?)
                'Technologies'              => 'Taxonomy\Technology',   // ~50 items, similar to `Industries` taxonomy (if kept, create Technologies instead of Market)
                'Coverage Type'             => 'Taxonomy\Coverage',     // 20+ items, Similar to website sections?

                // // Unused
                // 'DFP Ad Categories',
                // 'Sponsors',
            ],
            'Content'   => [
                '360_package_spin_rotate' => 'Website\\Content\\Product',      // Needs some custom field handling for the 3D display
                'apps'  => 'Website\\Content\\Product',                        // Custom field handling
                'around_the_world'  => 'Website\\Content\\Article',            // Around the world section/blog

                'article' => 'Website\\Content\\Article',                      // Make all Articles by default

                'page'  => 'Website\\Content\\Page',
                'blog'  => 'Website\\Content\\Blog',
                'company' => 'Website\\Content\\Company',
                'download'  => 'Website\\Content\\Document',
                // 'form_template'
                // 'leadership_data_card'                                       // Additional information about companies, unsure where used.
                // 'leadership_online_profile'                                  // More info,
                // 'leadership_print_profile'                                   // More info, print revision??
                'mini_bant' => 'Website\\Content\\TextAd',                      // Sponsored content, gated video/whitepaper landing page
                // 'mobile_webform'
                // 'opt_out_form'
                'playbook'  => 'Website\\Content\\Document',                    // May necessitate a custom content type, but a gated landing page for a PDF download
                'podcast' => 'Website\\Content\\Podcast',
                // 'pop_up_registration'                                        // Popup ad form pushing to omeda sub
                'registration_form' => 'Website\\Content\\Document',            // Majority appear to be PDF gates (some weird ones like ENL signups)
                'stage_one_form'  => 'Website\\Content\\TextAd',                // Landing pages/promo blurbs pushing to registration_forms
                'video' => 'Website\\Content\\Video',
                'webform' => 'Website\\Content\\TextAd',                        // Same as stage_one_form
                'webinar' => 'Website\\Content\\Webinar',                       // All old, currently unpublished
                'webinar_registration'  => 'Website\\Content\\Webinar',         // Current webinar landing form
                // 'webinar_series'
                'week_in_review'  => 'Website\\Content\\News',                  // News/sponsored review, "Beyond the Factory Walls" primary section??
                'whitepaper'  => 'Website\\Content\\Whitepaper',
            ],
            'Section'   => [
                // 'page' => 'Website\\Section',
                // 'page2' => 'Website\\Section',
            ],
            'Issue'     => [
                'magazine_covers' => 'Magazine\\Issue',                         // magazine cover image, digital edition url
            ],
            'database'          => 'drupal_pmmi_aw',
            'structure' =>  [
                'stripFields'   => [
                    // These fields will be removed from nodes before being written to mongo
                    'vid',
                    'log',
                    'uuid',
                    'tnid',
                    'cid',                          // comment id
                    'translate',
                    'print_html_display',
                    'print_html_display_comment',
                    'print_html_display_urllist',
                    'last_comment_uid',
                    'last_comment_name',
                    'last_comment_timestamp',
                    'revision_timestamp',           // looks to be same as 'changed' which we are using
                    'revision_uid',
                    'language',                     // language value, (und default)
                    'comment',
                    'comment_count',
                    'promote',
                    'sticky',
                    'vuuid',
                    'rdf_mapping',
                    'path',
                    'metatags',
                    'data',
                    'picture',
                    //'premium_content','comment_count','picture','data'  // other fields I see but leaving for now
                    //'moderate','format','feed','field_priority','field_leagcy_id','rdf_mapping','metatags','field_legacy_article_id');  // fields in old code, but not seen so far in hci
                    'field_360_multi_upload',
                    'field_360_images_reverse',
                    'field_360_magic_plugin',
                    'field_360_magic_plugin_columns',
                    'field_360_magic_plugin_rows',
                    'field_360_images',
                    'field_360_fc_large',
                    'field_gallery_360_field_location',
                    'field_allterms',   // @todo handle in taxonomy segment
                    'field_related',
                    'field_pop_up_registration',
                    'field_joomla_id',
                    'field_accela_id',
                    'field_duplicate',
                    'field_accelaworks_billcode',
                    'field_article_length',
                    'field_app_sponsor_news',
                    'field_brand_override',
                    'field_master_accelaworks_id',
                    'field_master_leadworks_id',
                    'field_form_header',

                    'field_post_launch_banner',
                    'field_pre_launch_banner',
                    'field_promoted',
                    'field_show_site_logo',
                    'field_silverpop_program_id',
                    'field_smg_pop_viddler',
                    'field_social_media_watch',
                    'field_sponsor_links',
                    'field_sponsor_logo',
                    'field_stage_one_form',
                    'field_vocab_primary_industry',
                    'field_vocab_primary',

                    'field_waywire_playlist_id',
                    'field_waywire_tag',
                    'field_waywire_video',
                    'field_webtracking_name',
                    'field_updated_on',
                    'field_top_copy',
                    'field_legacy',
                    'field_crosspost_to_pfw',
                    'field_reporting_from',
                    'webform',
                    'field_sales_reps', // no data

                    // Fields that may be important later, but exist in `legacy.raw`
                    'field_viddler_id',
                    'field_sponsor_expiration',
                    'field_omeda_behavior_ids',
                    'field_omeda_deployment_type_ids',
                    'field_omeda_download_attr_id',
                    'field_omeda_product_ids',
                    'field_omeda_promo_code',
                    'field_pull_quote',
                    'field_pullquote',
                    'field_top_copy',
                    'field_sub_title',
                    'field_app_more_information_link',
                    'field_application_case_history',
                    'field_platforms_os',
                    'field_video_resources', // Exist only on apps, part of the custom data presentation

                    'field_issue_date', // Issue date + page num appear to be references to print scheduling
                    'field_page_num',   // but don't contain enough data to map.


                    // 'field_wir_sponsor',
                    'field_youtube_uploads_id',
                    // 'field_youtube_username',
                    'field_youtube_username_override',

                    // Renamed fields, remove to cleanup
                    'nid',
                    'uid',
                    'title',
                    'field_deckhead',
                    'field_image',
                    'field_article_images',
                    'changed',
                    'field_byline',
                    'field_author_title',
                    // 'field_sub_podcasts',
                    // 'field_whitepaper',
                    // 'field_white_paper',     // Exist on Videos, contain youtube links??
                ],
                '_id'       => 'nid',
                'name'      => 'title',
                'status'    => 'status',
                'createdBy' => 'uid',
                'updatedBy' => 'revision_uid',  // was using uid for both but I changed - ok, or less reliable?  @jpdev
                'created'   => 'created',
                'updated'   => 'changed',
                'published' => 'created',
                'mutations' => 'path',
                'type'      => 'field_term_subtype.und.0.tid',
                'body'      => 'body.und.0.value',
                'teaser'    => 'field_deckhead.und.0.value',
                // 'authors'   => 'field_byline.und.0.value',
                // 'deck'      => 'field_deck.und.0.value',
                'images'    => ['field_image.und', 'field_article_images.und'],
                // // existing fields (used mainly by top100)
                // 'phone'     => 'field_phone.und.0.value',
                // 'city'      => 'field_hci100_location.und.0.value',
                // 'website'   => 'field_website.und.0.value',
                // 'socialLinks'       => ['field_twitter.und.0.value', '	field_linkedin.und.0.value', 'field_facebook.und.0.value'],
                // //'taxonomy'  => 'taxonomy',  // moving taxonomy refs in to legacy.refs from the outset, so not needed here (unless I move convertTaxonomy code into the convertFields method, which might be good)
                // // these are for the new top100 content type
                // 'rank'              => 'field_hci100_rank.und.0.value',
                // 'previousRank'      => 'field_hci100_previous_rank.und.0.value',
                // 'founded'           => 'field_hci100_founded.und.0.value',
                // 'companyType'       => 'field_hci100_company_type.und.0.value',
                // 'employees'         => 'field_hci100_employees.und.0.value',
                // 'revenueCurrent'    => 'field_hci100_revenue_current.und.0.value',
                // 'revenuePrior1'     => 'field_hci100_revenue_prior1.und.0.value',
                // 'revenuePriorYear1' => 'field_hci100_revenue_prior1_yyyy.und.0.value',
                // 'revenuePrior2'     => 'field_hci100_revenue_prior2.und.0.value',
                // 'revenuePriorYear2' => 'field_hci100_revenue_prior2_yyyy.und.0.value',
                // 'companyExecutives' => 'field_hci100_company_executives.und.0.value',
                // 'majorRevenue'      => 'field_hci100_major_revenue.und.0.value',
                // 'productCategories' => 'field_hci100_product_categories.und.0.value',
                // 'marketsServing'    => 'field_hci100_markets_serving.und.0.value',
                // 'linkUrl'           => 'function_getRedirects',
            ],
            'uri'   => 'https://www.automationworld.com',
        ],
    ];

    // strip fields so they do not show as unsupported, once identified as useless
    protected function removeCrapFields(&$node)
    {
        foreach ($this->map['structure']['stripFields'] AS $removeField) {
            unset($node->$removeField);
        }
    }

    // get drupal field names from base4 names via config map
    public function getFieldMapName($baseName)
    {
        $sourceField = null;
        if (empty($this->map['structure'][$baseName])) {
            $this->writeln(sprintf('Expected base field not mapped in config: %s', $baseName), true, true);
            throw new \InvalidArgumentException();
        } else {
            $sourceField = $this->map['structure'][$baseName];
            //if (count($sourceField == 1)) $sourceField = current($sourceField);
        }
        return $sourceField;
    }

    /**
     * {@inheritDoc}
     */
    protected function importWebsiteSectionNodes()
    {
        $this->writeln('Skipping website section import.');
    }

    /**
     * {@inheritdoc}
     */
    protected function getObjects($resource, $type = 'node')
    {
        $results = [];
        foreach ($resource as $row) {
            switch ($type) {
                case 'user':
                    $object = user_load($row->uid);
                    break;

                default:
                    $object = node_load($row->nid);
                    break;
            }
            if (is_object($object)) {
                $results[] = $object;
            }
        }
        return $results;
    }

    /**
     * {@inheritdoc}
     */
    protected function getRaw($resource)
    {
        return $resource->fetchAll();
    }

    /**
     * {@inheritdoc}
     */
    protected function formatValues(array $types = [])
    {
        return $types;
    }

    /**
     * {@inheritdoc}
     */
    protected function countNodes(array $types = [])
    {
        $types = $this->formatValues($types);
        $inQuery = implode(',', array_fill(0, count($types), '?'));
        $query = 'SELECT count(*) as count from {node} where type in ('.$inQuery.')';
        $resource = db_query($query, $types);
        $count = reset($this->getRaw($resource));
        return (int) $count->count;
    }

    /**
     * {@inheritdoc}
     */
    protected function queryNodes(array $types = [], $limit = 200, $skip = 0)
    {
        $types = $this->formatValues($types);
        $inQuery = implode(',', array_fill(0, count($types), '?'));
        $query = 'SELECT nid, type from {node} where type in ('.$inQuery.') ORDER BY nid desc';
        if (0 == $limit && $skip == 0) {
            $resource = db_query($query, $types);
        } else {
            $resource = db_query_range($query, $skip, $limit, $types);
        }

        $nodes = $this->getObjects($resource, 'node');
        // $this->writeln(sprintf('DEBUG: `%s` with `%s` returned %s results.', $query, $types, count($nodes)));
        return $nodes;
    }

    /**
     * {@inheritdoc}
     *
     * Many drupal values follow format of being field.$language.0.value
     * und is default language, this method basically returns the value through that chaff
     * Not consistently used, but probalby should be revised to do so
     *
     */
    protected function getFieldValue($field, $node, $return = null)
    {
        if (null === $field || empty($field)) {
            return $return;
        }
        if (isset($field[$node->language])) {
            return $this->getFieldValue($field[$node->language], $node, $return);
        }
        if (isset($field['und'])) {
            return $this->getFieldValue($field['und'], $node, $return);
        }
        return $field;
    }

    /**
     * {@inheritdoc}
     *
     * path to multidimentional array using dot notation in config
     *
     */
    public function resolveDotNotation(array $a, $path, $default = null) {
        $current = $a;
        $p = strtok($path, '.');
        while ($p !== false) {
            if (!isset($current[$p])) {
            return $default;
            }
        $current = $current[$p];
        $p = strtok('.');
        }
    return $current;
    }

    /**
     * {@inheritdoc}
     *
     * Tiered fallback for content where multiple fields can go to one base field, but there is an order of preference
     *
     */
    public function cascadeValue($doc, $cascadeSequence, $fallbackValue = null) {
        foreach ($cascadeSequence AS $cascadeElement) {
            $value = $this->resolveDotNotation($doc, $cascadeElement);
            if (!empty($value)) return $value;
        }
        return $fallbackValue;
    }

    /**
     * {@inheritdoc}
     *
     * First logic block generally takes care of everything.
     * Rest is artifact of earlier code, generally special fields specific to prior sites that are no vocabulary/taxonomy in drupal, but we want to treat them as such in base
     *
     */
    protected function convertTaxonomy(&$node)
    {
        $taxonomy = [];
        $vocabularyFields = $this->getTaxonomyFields();
        foreach ($vocabularyFields AS $vocabularyField) {
            if(!empty($node->$vocabularyField)) {
                $taxonomies = $node->$vocabularyField;
                foreach ($taxonomies['und'] AS $tax) {
                    $taxonomy[] = (String) $tax['tid'];
                }
            }
            unset($node->$vocabularyField);
        }

        if (isset($node->field_tags)) {
            $terms = $this->getFieldValue($node->field_tags, $node, []);
            foreach ($terms as $tax) {
                $taxonomy[] = (String) $tax['tid'];
            }
            unset($node->field_tags);
        }

        // Handle tagging/primary tag/primary section nonsense
        if (isset($node->field_special_focus)) {
            $terms = $this->getFieldValue($node->field_special_focus, $node, []);
            foreach ($terms as $tax) {
                $taxonomy[] = (String) $tax['tid'];
            }
            unset($node->field_special_focus);
        }

        if (isset($node->field_focus_sections)) {
            $terms = $this->getFieldValue($node->field_focus_sections, $node, []);
            foreach ($terms as $tax) {
                $taxonomy[] = (String) $tax['tid'];
            }
            unset($node->field_focus_sections);
        }

        if (isset($node->field_section)) {
            $terms = $this->getFieldValue($node->field_section, $node, []);
            foreach ($terms as $tax) {
                $taxonomy[] = (String) $tax['tid'];
            }
            unset($node->field_section);
        }

        $taxonomy = array_map(function($tid) {
            $term = taxonomy_term_load($tid);
            return sprintf('%s_%s', $term->vid, $tid);
        }, $taxonomy);

        // straight to legacy refs for resolution in base postimport segment
        if (!empty($taxonomy)) $node->legacy['refs']['taxonomy'] = $taxonomy;
    }

    /**
     * {@inheritdoc}
     *
     * Gets node fields which refer to taxonomy entries
     * Probably a cleaner way to do this, drupal default uses machine_name by default, but users can override, so not 100% reliable
     *
     */
    public function getTaxonomyFields() {
        if (null == $this->taxonomyFields) {
            $vocabs = taxonomy_get_vocabularies();
            foreach ($vocabs AS $vocab) {
                // @jpdev - not sure if there is a better way to determine field names, but so far they follow one of either of these formats
                $this->taxonomyFields[] = sprintf('taxonomy_%s', $vocab->machine_name);
                $this->taxonomyFields[] = sprintf('field_%s', $vocab->machine_name);

                // @jpdev - super hack for hci - no idea why the field does not follow the usual convention
                $this->taxonomyFields[] = 'field_pop_health_data_analytics';  // machine name is population_health_data_analytics not pop_health_data_analytics which is added by logic above
            }
        }

        return $this->taxonomyFields;
    }

    /**
     * {@inheritdoc}
     *
     * Looks up taxonomy by tid in drupal so it can determine its type and thus where its mapped to in base via config
     * Not used for HCI (because said fields did not exist) but can see need in future
     *
     */
    protected function addTerm(&$taxonomy, $tid)
    {
        $tax = taxonomy_term_load($tid);
        $v = taxonomy_vocabulary_load($tax->vid);

        if (null !== ($type = (isset($this->map['Taxonomy'][$v->name])) ? $this->map['Taxonomy'][$v->name] : null)) {
            $type = str_replace('Taxonomy\\', '', $type);
            $taxonomy[] = [
                'id'    => (String) $tid,
                'type'  => $type
            ];
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function importContentTypeNodes($type, $limit = 200)
    {
        $counter = function() use ($type) {
            return $this->countNodes([$type]);
        };

        $retriever = function($num, $skip) use ($type) {
            return $this->queryNodes([$type], $num, $skip);
        };

        $modifier = function($node) use ($type) {
            $nid = (int) $node->nid;
            if (0 === $nid) return;
            $this->convertLegacy($node);
            $this->convertTaxonomy($node);
            $this->convertScheduling($node);
            $this->convertFields($node);
            $set = json_decode(json_encode($node, 512), true);
            $dateFields = ['created', 'updated', 'published', 'unpublished'];
            foreach ($dateFields as $field) {
                if (isset($set[$field])) {
                    $set[$field] = new UTCDateTime((int) $set[$field] * 1000);
                }
            }
            return [
                'filter'    => [ '_id'  => $nid ],
                'update'    => [ '$set' => $set ],
            ];
        };

        $persister = function($ops) {
            $this->dbal->batchUpsert($this->database, 'Content', $ops);
        };


        $this->loop($counter, $retriever, $modifier, $persister, sprintf('Content (%s)', $type), $limit);
    }

    /**
     * {@inheritDoc}
     */
    protected function importMagazineIssueTypeNodes($type, $limit = 200)
    {
        $tz = new DateTimeZone('America/Chicago');

        $counter = function() use ($type) {
            return $this->countNodes([$type]);
        };

        $retriever = function($num, $skip) use ($type) {
            return $this->queryNodes([$type], $num, $skip);
        };

        $modifier = function($node) use ($type, $tz) {
            $nid = (int) $node->nid;
            if (0 === $nid) return;

            $set = [
                '_id'               => (int) $node->nid,
                'name'              => $node->title,
                'created'           => (int) $node->created,
                'updated'           => (int) $node->changed,
                'status'            => (int) $node->status,
                'legacy'            => [
                    'id'                => (string) $node->nid,
                    'source'            => sprintf('%s_issue_%s', $this->getKey(), $node->type),
                    'raw'               => $node,
                ],
            ];

            if (!empty($node->body)) $set['description'] = $node->body;

            $mailDate = strtotime(str_replace('Automation World', '', $node->title));
            if ($mailDate === false) {
                $title = trim($node->title);
                $title = str_replace('Automation World - ', '', $title);
                // Try and regex parse out ".*\w{3} \d{4}" or similar to get IIOT suppliement asdfasdf november 2018
                if (\preg_match('/^.*?([\w]{1,}\s[\d]{2,})/i', $title, $matches)) $title = $matches[1];

                $mailDate = strtotime($title);
                if(!$mailDate) $this->writeln(sprintf('Unable to parse mailDate from title %s.', $node->title));
            }
            $mailDate = new DateTime(date('c', $mailDate), $tz);

            if (false !== $mailDate) {
                $set['mailDate'] = $mailDate->format('c');
                $set['legacy']['shortName'] = strtolower($mailDate->format('My'));
            }

            // coverImage
            if (!empty($node->field_image)) {
                $imageData = $node->field_image['und'][0];
                $fp = $this->createImage($imageData);
                $set['legacy']['refs']['coverImage']['common'] = $fp;
            }

            // digital edition links
            if (!empty($node->field_link)) {
                $url = $node->field_link['und'][0];
                $set['digitalEditionUrl'] = $url['url'];
            }

            return [
                'filter'    => [ '_id'  => $nid ],
                'update'    => [ '$set' => $set ],
            ];
        };

        $persister = function($ops) {
            $this->dbal->batchUpsert($this->database, 'Issue', $ops);
        };


        $this->loop($counter, $retriever, $modifier, $persister, sprintf('Issue (%s)', $type));
    }

    protected $term_cache = [];
    protected $vocab_cache = [];

    protected function loadVocabMachineName($vid)
    {
        $id = (int) $vid;
        if (!isset($this->vocab_cache[$id])) {
            $vocab = taxonomy_vocabulary_load($id);
            $this->vocab_cache[$id] = $vocab->machine_name;
        }
        return $this->vocab_cache[$id];
    }

    /**
     * {@inheritdoc}
     *
     * Reformats node data into consumable fields
     */
    protected function convertFields(&$node)
    {
        $nodeArray = json_decode(json_encode($node, 512), true);

        // type
        $node->type = str_replace('Website\\Content\\', '', $this->map['Content'][$node->type]);
        $tid = $this->resolveDotNotation($nodeArray, 'field_term_subtype.und.0.tid');
        if ($tid) {
            if (!isset($this->term_cache[$tid])) {
                $type = taxonomy_term_load($tid);
                $this->term_cache[$tid] = $type->name;
            }
            $type = $this->term_cache[$tid];
            if (in_array($type, ['News'])) $node->type = 'News';
            if (in_array($type, ['Perspective', 'Column'])) $node->type = 'Blog';
        }

        // _id
        $node->_id = (int) $node->nid;
        $node->name = $node->title;
        $node->status = (int) $node->status;

        $node->legacy['refs']['createdBy']['aw_user'] = $node->uid;
        $node->legacy['refs']['updatedBy']['aw_user'] = $node->revision_uid;

        $node->created = (int) $node->created;
        $node->updated = (int) $node->changed;
        $node->published = (int) $node->created;

        $unpublished = $this->resolveDotNotation($nodeArray, 'field_expiration_date.und.value');
        if ($unpublished) $node->unpublished = (int) $unpublished;
        unset($node->field_expiration_date);

        // Redirects
        $redirects = &$node->mutations['Website']['redirects'];
        $redirects[] = sprintf('node/%s', $node->_id);

        $alias = drupal_get_path_alias(sprintf('node/%s', $node->_id));
        if ($node->type === 'Page') {
            $node->mutations['Website']['alias'] = $alias;
        } else {
            $redirects[] = $alias;
        }
        $q = sprintf("SELECT source from {redirect} where source = 'node/%s'", $node->nid);
        foreach (db_query($q) as $r) $redirects[] = $r->source;

        // body
        $body = $this->resolveDotNotation($nodeArray, 'body.und.0.value');
        $node->body = $body;
        if (empty($body)) {
            unset($node->body);
        }

        // teaser
        $teaser = $this->resolveDotNotation($nodeArray, 'field_deckhead.und.0.value');
        if (!empty($teaser)) $node->teaser = $teaser;

        $author = $this->resolveDotNotation($nodeArray, 'field_byline.und.0.value');
        if ($author) {
            list($first, $last) = explode(' ', $author, 2);
            $title = $this->resolveDotNotation($nodeArray, 'field_author_title.und.0.value');
            $this->importContact([
                'firstName' => $first,
                'lastName' => $last,
                'title' => $title,
            ]);
            $legacySource = sprintf('%s_contacts', $this->getKey());
            $node->legacy['refs']['authors'][$legacySource][] = trim($author);
        }

        // Images
        $primary = $this->resolveDotNotation($nodeArray, 'field_image.und.0');
        if ($primary) {
            $fp = $this->createImage($primary);
            $node->legacy['refs']['primaryImage']['common'] = $fp;
        }
        unset($node->field_image);

        $cover = $this->resolveDotNotation($nodeArray, 'field_cover_image.und.0');
        if ($cover) {
            $fp = $this->createImage($cover);
            $node->legacy['refs']['primaryImage']['common'] = $fp;
        }
        unset($node->field_cover_image);

        $images = $this->resolveDotNotation($nodeArray, 'field_article_images.und');
        if (!empty($images)) {
            foreach ($images as $image) {
                $fp = $this->createImage($image);
                $node->legacy['refs']['images']['common'][] = $fp;
            }
        }
        unset($node->field_article_images);

        $images = $this->resolveDotNotation($nodeArray, 'field_360_multi_upload.und');
        if (!empty($images)) {
            foreach ($images as $image) {
                $fp = $this->createImage($image);
                $node->legacy['refs']['images']['common'][] = $fp;
            }
        }
        unset($node->field_360_multi_upload);

        $companies = $this->resolveDotNotation($nodeArray, 'field_companies.und');
        if (!empty($companies)) {
            foreach ($companies as $ref) {
                $node->legacy['refs']['companies'][] = $ref['nid'];
            }
        }
        unset($node->field_companies);

        // Taxonomy
        $taxFields = ['coverage', 'source_type', 'coverage_type', 'column_type', 'subtype', 'company_type'];
        // @todo check field_term_vocab, field_term_vocab_primary_industry, field_allterms
        foreach ($taxFields as $type) {
            $refs = $this->resolveDotNotation($nodeArray, sprintf('field_term_%s.und', $type));
            if ($refs) {
                foreach ($refs as $ref) {
                    $term = taxonomy_term_load($ref['tid']);
                    $id = sprintf('%s_%s', $term->vid, $ref['tid']);
                    $node->legacy['refs']['taxonomy'][] = [
                        '$ref'  => 'Taxonomy',
                        '$id'   => $id,
                        '$db'   => 'drupal_pmmi_aw',
                        'type'  => $type
                    ];
                }
            }
            unset($node->{sprintf('field_term_%s', $type)});
        }

        $allTerms = $this->resolveDotNotation($nodeArray, 'field_allterms.und');
        if (!empty($allTerms)) {
            foreach ($allTerms as $ref) {
                $term = taxonomy_term_load($ref['tid']);
                $id = sprintf('%s_%s', $term->vid, $ref['tid']);
                $node->legacy['refs']['taxonomy'][] = [
                    '$ref'  => 'Taxonomy',
                    '$id'   =>  $id,
                    '$db'   => 'drupal_pmmi_aw',
                    'type'  => $this->loadVocabMachineName($ref['vid']),
                ];
            }
        }
        unset($node->field_allterms);

        // Related To
        $relatedTo = $this->resolveDotNotation($nodeArray, 'field_related.und');
        if (!empty($relatedTo)) {
            foreach ($relatedTo as $ref) {
                $node->legacy['refs']['relatedTo'][] = $ref['nid'];
            }
        }
        unset($node->field_related);

        // Address data
        $address1 = $this->resolveDotNotation($nodeArray, 'field_address1.und.value');
        if ($address1) $node->address1 = $address1;
        unset($node->field_address1);

        $address2 = $this->resolveDotNotation($nodeArray, 'field_address2.und.value');
        if ($address2) $node->address1 = $address2;
        unset($node->field_address2);

        $city = $this->resolveDotNotation($nodeArray, 'field_city.und.value');
        if ($city) $node->city = $city;
        unset($node->field_city);

        $country = $this->resolveDotNotation($nodeArray, 'field_country.und.value');
        if ($country) $node->country = $country;
        unset($node->field_country);

        $zip = $this->resolveDotNotation($nodeArray, 'field_zipcode.und.value');
        if ($zip) $node->zip = $zip;
        unset($node->field_zipcode);

        $state = $this->resolveDotNotation($nodeArray, 'field_state.und.value');
        if ($state) $node->state = $state;
        unset($node->field_state);

        $fax = $this->resolveDotNotation($nodeArray, 'field_fax.und.value');
        if ($fax) $node->fax = $fax;
        unset($node->field_fax);

        $phone = $this->resolveDotNotation($nodeArray, 'field_phone.und.value');
        if ($phone) $node->phone = $phone;
        unset($node->field_phone);

        // Sidebars
        $blockquote = $this->resolveDotNotation($nodeArray, 'field_blockquote.und.value');
        if ($blockquote) {
            $node->sidebars[] = $blockquote;
        }
        unset($node->field_blockquote);

        // Podcasts
        $podcast = $this->resolveDotNotation($nodeArray, 'field_podcast.und');
        if ($podcast) {
            $podcast['_uri'] = file_create_url($podcast['uri']);
            $node->legacy['refs']['files'][] = $podcast;
        }
        unset($node->field_podcast);

        // Some podcasts support `sub podcasts` -- additional files/tracks uploaded to the podcast. Base won't
        // These may need to be brought in as related content or something in the future.
        unset($node->field_sub_podcasts);


        // News
        // field_link.und.{title,url} link to news source -- embed in content body?
        $company = $this->resolveDotNotation($nodeArray, 'field_wir_sponsor.und.target_id');
        if ($company) {
            $node->legacy['refs']['company'] = (int) $company;
        }
        unset($node->field_wir_sponsor);

        // Videos
        // field_white_paper // Exist on Videos, contain youtube links??
        $viddler = $this->resolveDotNotation($nodeArray, 'field_viddler_id.und.0');
        if ($viddler) {
            $fp = $this->createImage($viddler, null, $node->created);
            $node->legacy['refs']['primaryImage']['common'] = $fp;
            $node->legacy['refs']['images']['common'][] = $fp;
            $node->embedCode = $viddler['embed_code'];
        }

        // Whitepapers
        // field_whitepaper

        // Document
        // field_top_copy
        // field_eyebrow
        $files = $this->resolveDotNotation($nodeArray, 'field_download_document.und');
        if (!empty($files)) {
            foreach ($files as $file) {
                $file['_uri'] = file_create_url($file['uri']);
                $node->legacy['refs']['files'][] = $file;
            }
        }
        unset($node->field_download_document);

        $files = $this->resolveDotNotation($nodeArray, 'field_content_pdf.und');
        if (!empty($files)) {
            foreach ($files as $file) {
                $file['_uri'] = file_create_url($file['uri']);
                $node->legacy['refs']['files'][] = $file;
            }
        }
        unset($node->field_content_pdf);

        // Apps (Product)
        // field_app_more_information_link  // Array of url/link text
        // field_application_case_history   // node refernce
        // field_platforms_os               // arraya of value/revids --- match taxonomy?
        // field_video_resources',          // Exist only on apps, part of the custom data presentation?

        // @todo Playbook support
        //  field_expert, field_sponsor (array of values and revision ids?)
        //  field_sub_title, field_playbook_name, field_playbook_pdf, field_disclaimer

        // Companies
        $youtube = $this->resolveDotNotation($nodeArray, 'field_youtube_username.und.value');
        if ($youtube) $node->socialLinks[] = [
            'provider'  => 'youtube',
            'label'     => 'Youtube',
            'url'       => sprintf('https://youtube.com/%s', $youtube)
        ];
        unset($node->field_youtube_username);

        $logo = $this->resolveDotNotation($nodeArray, 'field_logo.und');
        if ($logo) {
            $fp = $this->createImage($logo);
            $node->legacy['refs']['logo'][] = $fp;
        }
        unset($node->field_logo);

        $link = $this->resolveDotNotation($nodeArray, 'field_link.und.url');
        if ($link) $node->website = $link;
        unset($node->field_link);

        // Leadership Session
        $tid = $this->resolveDotNotation($nodeArray, 'field_ld_session.und.tid');
        if ($tid) {
            $term = taxonomy_term_load($tid);
            $id = sprintf('%s_%s', $term->vid, $tid);
            $node->legacy['refs']['taxonomy'][] = [
                '$ref'  => 'Taxonomy',
                '$id'   => $id,
                '$db'   => 'drupal_pmmi_aw',
                'type'  => 'leadership_session',
            ];
        }
        unset($node->field_ld_session);






        // $oldFields = ['field_image_caption']; // caption used with field_image
        // $newFields = ['_id', 'type', 'legacy', ];
        // foreach ($this->map['structure'] AS $baseFieldName => $drupalFieldNames) {
        //     $newFields[] = $baseFieldName;
        //     if (!is_array($drupalFieldNames)) $drupalFieldNames = (array)$drupalFieldNames;

        //     // dot notation drupal fields will remove fromt the root element (body.value will remove body and all children unless body is in the newField list as well)
        //     foreach ($drupalFieldNames AS &$drupalFieldName) {
        //         $drupalFieldName = explode('.', $drupalFieldName)[0];
        //     }
        //     $oldFields = array_unique(array_merge($oldFields, $drupalFieldNames));
        // }
        // $removeFields = array_diff($oldFields, $newFields);
        // foreach ($removeFields AS $removeField) {
        //     unset($node->$removeField);
        // }
        // $nodeFields = array_keys($nodeArray);

        // // don't move fields that are in the newField (B4) list
        // $moveFields = array_diff($nodeFields, $newFields);

        // // don't move fields that were sources from the oldField (D7) list
        // $moveFields = array_diff($moveFields, $oldFields);

        // foreach ($moveFields AS $moveField) {
        //     $unsupportedFields[$moveField] = $node->$moveField;
        //     unset($node->$moveField);
        // }
        // $node->legacy['unsupportedFields'] = $unsupportedFields;
        $this->removeCrapFields($node);
    }



    /**
     * {@inheritdoc}
     *
     * Creates an image to create as Asset in base4
     *
     */
    protected function createImage(array $img, $caption = null, $date = null)
    {
        if (isset($img['thumbnail_fid'])) {
            $id = (int) $img['thumbnail_fid'];
            $url = $img['thumbnail_url'];
            $name = basename($url);
            $date = $date ? date('c', $date) : date('c');
        } else {
            $id = (int) $img['fid'];
            if ($id === 0) return;
            $url = file_create_url($img['uri']);
            $url = str_replace('http://default', $this->map['uri'], $url);
            $name = $img['filename'];
            $date = date('c', $img['timestamp']);
        }

        $newName = sprintf('%s_%s_%s', $this->getKey(), $id, $name);

        $kv = [
            '_id'       => $id,
            'type'      => 'Image',
            'name'      => $newName,
            'fileName'  => $newName,
            'source'    => [
                'location'  => $url,
                'name'      => $name,
            ],
            'caption'   => $img['title'],
            'alt'       => $img['alt'],
            'legacy'    => [
                'id'        => $id,
                'source'    => sprintf('common'),
                'created'   => $date,
            ]
        ];

        $filter = ['_id' => $id];
        $update = ['$set' => $kv];
        $this->dbal->upsert($this->database, 'Image', $filter, $update);
        return $url;
    }

    /**
     * {@inheritdoc}
     *
     * Notes: https://www.drupal.org/project/term_merge/issues/2104769
     * taxonomy_get_tree will return duplicates when encountering vocab nodes with
     * multiple parents. Therefore, key the formatted result set, force parent to
     * a single value, and then array_values the end results for import.
     *
     * This shouldn't matter now that we're doing bulk upserts, as it should just
     * be overwritten.
     *
     */
    protected function importTaxonomy($vocab)
    {
        // Insert the root vocab for hierarchical vocabs
        $vid = $vocab->vid;
        $type = str_replace('Taxonomy\\', '', $this->map['Taxonomy'][$vocab->name]);
        if (!in_array($type, ['Bin', 'Tag'])) {
            $filter = ['_id' => $vid];
            $update = [
                '$set'  => [
                    '_id'   => $vid,
                    'type'  => $type,
                    'name'  => $vocab->name,
                    'legacy'    => [
                        '_id'       => $vid,
                        'source'    => sprintf('%s_vocab', $this->getKey()),
                    ],
                ],
            ];
            $this->dbal->upsert($this->database, 'Taxonomy', $filter, $update);
        }

        $limit = 200;
        $terms = taxonomy_get_tree($vocab->vid);
        $count = count($terms);
        $terms = array_chunk($terms, $limit);

        $counter = function() use ($count) {
            return $count;
        };

        $retriever = function($limit, $skip) use ($terms) {
            $index = $skip / $limit;
            return $terms[$index];
        };

        $modifier = function($term) use ($vocab, $vid, $type) {
            $tid = (int) $term->tid;
            if ($tid === 0) return;
            $alias = taxonomy_term_uri($term)['path'];
            if (false !== $path = drupal_lookup_path('alias', $alias)) $alias = $path;
            $parent = (int) array_pop($term->parents);
            if ($parent && $parent !== 0) {
                $parent = [
                    'id'        => sprintf('%s_%s', $vid, $parent),
                    'source'    => sprintf('%s_term', $this->getKey()),
                ];
            } else {
                if (!in_array($type, ['Bin', 'Tag'])) {
                    $parent = [
                        'id'        => $vid,
                        'source'    => sprintf('%s_vocab', $this->getKey()),
                    ];
                }
            }
            $id = sprintf('%s_%s', $vid, $tid);

            $op = [
                'filter'    => [ '_id' => $id ],
                'update'    => [
                    '$set'  => [
                        '_id'       => $id,
                        'type'      => $type,
                        'name'      => $type === 'Bin' ? sprintf('%s: %s', $vocab->name, $term->name) : $term->name,
                        'alias'         => $alias,
                        'legacy'        => [
                            'id'            => $id,
                            'source'        => sprintf('%s_term', $this->getKey()),
                            'vid'           => $vid,
                        ],
                    ]
                ],
            ];

            if ($parent) $op['update']['$set']['legacy']['refs']['parent'] = $parent;
            if ($term->description) $op['update']['$set']['description'] = $term->description;
            return $op;
        };

        $persister = function($ops) {
            $this->dbal->batchUpsert($this->database, 'Taxonomy', $ops);
        };

        $this->loop($counter, $retriever, $modifier, $persister, sprintf('Taxonomy (%s)', $vocab->name), $limit);
    }

    /**
     * {@inheritdoc}
     *
     * Creates an image to create as Asset in base4
     *
     */
    protected function importContact($contact)
    {
        $contact['type'] = 'Contact';
        $contact['name'] = sprintf('%s %s', $contact['firstName'], $contact['lastName']);
        $contact['legacy'] = ['id' => $contact['name'], 'source' => 'aw_contacts'];

        $filter = ['legacy.id' => $contact['name']];
        $update = ['$set' => $contact];
        return $this->dbal->upsert($this->database, 'Content', $filter, $update);
    }


    /**
     * {@inheritdoc}
     *
     * The other end of magaizne scheduling (node->issue), Issue import storing the reverse (issue->node) - not same data, using both
     *
     */
    protected function convertScheduling(&$node)
    {
        $nid = $node->nid;
        $issue = $node->field_magazine_issue;
        if (!empty($issue)) {
            $issueNid = $issue['und'][0]['nid'];
            if (!empty($issueNid)) {
                $issue = node_load($issueNid);

                // original code stored to its own collection, atm I'm storing in legacy.refs.schedule straight away
                $node->legacy['schedule']['print']['issue'][] = (string) $issue->nid;
                $this->addMagazineSchedule($node, $issue);
            }
        }
        unset($node->field_issue);
    }

    /**
     * {@inheritdoc}
     *
     * Don't use this collection at all anymore, leaving for now but no used by base4 end
     *
     */
    protected function addMagazineSchedule($node, $issue)
    {
        $collection = $this->database->selectCollection('ScheduleMagazine');
        $type = (isset($this->map['Content'][$node->type])) ? $this->map['Content'][$node->type] : null;
        $type = str_replace('Website\\Content\\', '', $type);
        $kv = [
            'contentId'   => (int) $node->nid,
            'contentType'  => $type,
            'issue'     => (int) $issue->nid,
            'section'   => null
        ];
        $collection->insert($kv);
    }

}
