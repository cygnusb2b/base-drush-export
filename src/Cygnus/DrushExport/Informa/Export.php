<?php

namespace Cygnus\DrushExport\Informa;

use Cygnus\DrushExport\AbstractExport;
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
abstract class Export extends AbstractExport
{
    protected $debugNid = 2;

    /**
     * Main export function.
     */
    public function execute()
    {
        $this->writeln(sprintf('Starting import for %s', $this->key));

        // $this->importUsers();
        // $this->importTaxonomies();
        $this->importNodes();

        $this->writeln('Import complete.', true, true);
    }

    /**
     * Iterates over nodes and exports them.
     */
    protected function importNodes()
    {
        $this->writeln('Importing Nodes.', false, true);
        $this->indent();

        // $this->importWebsiteSectionNodes();
        // $this->importMagazineIssueNodes();
        $this->importContentNodes();

        $this->outdent();
    }

    // strip fields so they do not show as unsupported, once identified as useless
    protected function removeCrapFields(&$node)
    {
        var_dump(__method__);
        foreach ($this->map['structure']['stripFields'] AS $removeField) {
            unset($node->$removeField);
        }
        foreach ($node as $key => $value) {
            if (empty($value)) unset($node->{$key});
            if (stristr($key, 'field_')) {
                //var_dump('unsetting field_ keys');
                //var_dump($key);
                unset($node->{$key});
            }
        }

        // @jpdev - fix this, no good now
        $node->legacy['raw'] = isset($node->legacy['raw']) ? $node->legacy['raw'] : [];
        foreach ($node->legacy['raw'] as $key => $value) {
            if (empty($value)) unset($node->legacy['raw'][$key]);
        }

        // @jpdev - specific to hct?  removing to confirm
        //$badRaw = ['data', 'rdf_mapping', 'field_alchemy_concepts', 'field_alchemy_entities', 'field_metadescriptionlong', 'field_metakeywordslong'];
        $badRaw = [];
        foreach ($badRaw as $field) {
            unset($node->legacy['raw'][$field]);
        }
        // Remove bodies, some are too long
        if ($this->getKey() == 'mnet') {
            var_dump('removing mnet');
            unset($node->legacy['raw']['body']);
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
        $debugQuery = ($this->debugNid) ? ' and nid = ' . $this->debugNid : '';
        $query = sprintf('SELECT count(*) as count from {node} where type in (%s) %s', $inQuery, $debugQuery);
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
        $debugQuery = ($this->debugNid) ? ' and nid = ' . $this->debugNid : '';
        $query = sprintf('SELECT nid, type from {node} where type in (%s) %s ORDER BY nid desc', $inQuery, $debugQuery);
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
        $language = $node->language ? $node->language : 'und';
        $taxonomy = [];
        $vocabularyFields = $this->getTaxonomyFields();
        foreach ($vocabularyFields AS $vocabularyField) {
            if(!empty($node->$vocabularyField)) {
                $taxonomies = $node->$vocabularyField;
                foreach ($taxonomies[$language] AS $tax) {
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

        if (isset($node->field_taxonomy)) {
            $terms = $this->getFieldValue($node->field_taxonomy, $node, []);
            foreach ($terms as $tax) {
                $taxonomy[] = (String) $tax['tid'];
            }
            unset($node->field_taxonomy);
        }

        if (isset($node->field_product_companies)) {
            $terms = $this->getFieldValue($node->field_product_companies, $node, []);
            foreach ($terms as $tax) {
                $taxonomy[] = (String) $tax['tid'];
            }
            unset($node->field_product_companies);
        }

        $taxonomy = array_map(function($tid) {
            $term = taxonomy_term_load($tid);
            return sprintf('%s_%s', $term->vid, $tid);
        }, $taxonomy);

        // straight to legacy refs for resolution in base postimport segment
        if (!empty($taxonomy)) $node->legacy['refs']['taxonomy'][$this->getKey()] = $taxonomy;
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
            $dateFields = ['created', 'updated', 'published', 'unpublished', 'startDate', 'endDate'];
            foreach ($dateFields as $field) {
                if (isset($set[$field])) {
                    $set[$field] = new UTCDateTime((int) $set[$field] * 1000);
                }
            }
            if (isset($set['legacy']['refs']['schedules'][$this->getKey()])) {
                $refs = &$set['legacy']['refs']['schedules'][$this->getKey()];
                foreach ($refs as &$schedule) {
                    $schedule['startDate'] = new UTCDateTime((int) $schedule['startDate'] * 1000);
                    if ($schedule['endDate']) $schedule['endDate'] = new UTCDateTime((int) $schedule['endDate'] * 1000);
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

        $skip = 0; // Change to skip aheadz
        $this->loop($counter, $retriever, $modifier, $persister, sprintf('Content (%s)', $type), $limit, $skip);
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
            $language = $node->language ? $node->language : 'und';

            unset($node->field_image[$language][0]['metatags']);
            $nodeArray = json_decode(json_encode($node, 512), true);

            $publication = $this->configs[$this->getKey()]['name'];

            $title = str_replace(sprintf('%s - ', $publication), '', $node->title);
            $title = str_replace(sprintf('%s\'s', $publication), '', $title);
            $title = str_replace($publication, '', $title);
            $title = str_replace('Digital Edition', '', $title);
            $title = str_replace('  ', ' ', $title);
            $title = trim($title);

            $set = [
                '_id'               => (int) $node->nid,
                'name'              => $title,
                'created'           => (int) $node->created,
                'updated'           => (int) $node->changed,
                'status'            => (int) $node->status,
                'legacy'            => [
                    'id'                => (string) $node->nid,
                    'source'            => sprintf('%s_issue_%s', $this->getKey(), $node->type),
                    'raw'               => $nodeArray,
                ],
            ];

            if (!empty($node->body)) $set['description'] = $node->body;
            if (is_array($set['description'])) {
                $set['description'] = $this->resolveDotNotation($nodeArray, sprintf('body.%s.0.value', $language));
            }

            $issueDate = $this->resolveDotNotation($nodeArray, sprintf('field_issue_date.%s.0.value', $language));
            $date = $this->resolveDotNotation($nodeArray, sprintf('field_date.%s.0.value', $language));
            if ($issueDate) {
                $mailDate = new DateTime(date('c', $issueDate));
            } elseif ($date) {
                $mailDate = new DateTime(date('c', $date));
            } else {
                $mailDate = strtotime($title);
                if ($mailDate === false) {
                    // Try and regex parse out ".*\w{3} \d{4}" or similar to get IIOT suppliement asdfasdf november 2018
                    if (\preg_match('/^.*?([\w]{1,}\s[\d]{2,})/i', $title, $matches)) $title = $matches[1];
                    $mailDate = strtotime($title);
                    if(!$mailDate) {
                        $mailDate = (int) $node->created;
                        if (!$mailDate) {
                            $this->writeln(sprintf('Unable to parse mailDate from title %s.', $node->title));
                            var_dump($node);
                            die();
                        }
                    }
                }
                $mailDate = new DateTime(date('c', $mailDate), $tz);
            }

            if (false !== $mailDate) {
                $set['mailDate'] = $mailDate->format('c');
                $set['legacy']['shortName'] = strtolower($mailDate->format('My'));
            }

            $tid = $this->resolveDotNotation($nodeArray, sprintf('field_term_media.%s.0.tid', $language));
            if ($tid) {
                $term = taxonomy_term_load($tid);
                $set['legacy']['refs']['publication'] = ['tid' => $tid, 'vid' => $term->vid, 'name' => $term->name];
            }

            // coverImage
            $image = $this->resolveDotNotation($nodeArray, sprintf('field_image.%s.0', $language));
            if (!$image) $image = $this->resolveDotNotation($nodeArray, sprintf('field_magazine_image.%s.0', $language));
            if (!$image) $image = $this->resolveDotNotation($nodeArray, sprintf('field_digital_cover_image.%s.0', $language));
            if ($image) {
                $fp = $this->createImage($image);
                $set['legacy']['refs']['coverImage'][$this->getKey()] = $fp;
            }

            // digital edition links
            $link = $this->resolveDotNotation($nodeArray, sprintf('field_link.%s.0.url', $language));
            if (!$link) $link = $this->resolveDotNotation($nodeArray, sprintf('field_texterity_url.%s.0.url', $language));
            if ($link) $set['digitalEditionUrl'] = $link;

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
     * Handles images
     */
    protected function convertImages(&$node, $nodeArray)
    {
        var_dump(__method__);
        var_dump('need to update image fields');
        $images = [];
        $fields = [
            // Primary refs
            'field_image',
            'field_featured_image',
            'field_cover_image',
            'field_viddler_id',
            'field_product_image',
            // Normal refs
            'field_article_images',
            'field_sponsors',
            'field_360_multi_upload',
            'field_digital_cover_image',
            'field_events_thumbnail_default',
            'field_medium_landscape',
            'field_newsletter_ad_rev_image',
            'field_newsletter_banner_image',
            'field_newsletter_boombox_image',
            'field_newsletter_text_ad_image',
        ];

        $language = $node->language ? $node->language : 'und';

        foreach ($fields as $field) {
            $items = $this->resolveDotNotation($nodeArray, sprintf('%s.%s', $field, $language), []);
            if (!empty($items)) {
                foreach ($items as $image) {
                    $fp = $this->createImage($image);
                    $this->pushImageRef($node, $fp);
                }
            }
            unset($node->{$field});
        }
    }

    protected function pushImageRef(&$node, $fp)
    {
        $node->legacy['refs']['images'][$this->getKey()][] = $fp;
        if (!isset($node->legacy['refs']['primaryImage'][$this->getKey()])) {
            $node->legacy['refs']['primaryImage'][$this->getKey()] = $fp;
        }
    }

    /**
     * Handles
     */
    protected function handleParagraphs(&$node, $paragraphs)
    {
        $language = $node->language ? $node->language : 'und';
        if ($paragraphs) {
            $items = [];
            $ids = array_map(function($arr) { return $arr['value']; }, $paragraphs);
            foreach ($ids as $id) {
                $item = @paragraphs_item_load($id);
                if (!$item) continue;
                $itemArray = json_decode(json_encode($item, 512), true);
                // @todo review captions and 0.value change
                switch ($item->bundle) {
                    case 'embedded_text':
                        $items[] = $this->resolveDotNotation($itemArray, sprintf('field_embedded_text.%s.0.value', $language));
                        break;
                    case 'embedded_image':
                        $caption = $this->resolveDotNotation($itemArray, sprintf('field_embedded_image_caption.%s.0.value', $language));
                        $image = $this->resolveDotNotation($itemArray, sprintf('field_paragraphs_embedded_image.%s.0', $language));
                        if ($image) {
                            $fp = $this->createImage($image, $caption);
                            $this->pushImageRef($node, $fp);
                            $items[] = sprintf('<div class="embedded-image"><img src="%s" /></div>', $fp, $caption);
                        }
                        break;
                    case 'headshot_widget':
                        $author = $this->resolveDotNotation($itemArray, sprintf('field_author.%s.0.value', $language));
                        $image = $this->resolveDotNotation($itemArray, sprintf('field_image.%s.0', $language));
                        $jobTitle = $this->resolveDotNotation($itemArray, sprintf('field_job_title.%s.0.value', $language));
                        $caption = $jobTitle ? sprintf('%s, %s', $author, $jobTitle) : $author;
                        if ($image) {
                            $fp = $this->createImage($image, $caption);
                            $this->pushImageRef($node, $fp);
                            $items[] = sprintf('<div class="embedded-image"><img src="%s" data-align="right" /></div>', $fp);
                        }
                        break;
                    case 'embedded_video':
                        $embed = $this->resolveDotNotation($itemArray, sprintf('field_embedded_video_code.%s.0.value', $language));
                        if ($node->type === 'Video') {
                            $node->embedCode = $embed;
                        } else {
                            $items[] = sprintf('<div class="embedded-video">%s</div>', $embed);
                        }
                        break;
                    case 'embedded_audio':
                        $code = $this->resolveDotNotation($itemArray, sprintf('field_embedded_audio_code.%s.0.value', $language));
                        if ($code) $items[] = sprintf('<div class="embedded-audio">%s</div>', $code);
                        break;
                    case 'embedded_twitter_card':
                        $markup = $this->resolveDotNotation($itemArray, sprintf('field_twitter_card_html_markup.%s.0.value', $language));
                        if (preg_match_all('/href="(.+?)"/i', $markup, $matches) > 0) {
                            $url = array_pop($matches[1]);
                            $items[] = sprintf('%%{[ data-embed-type="oembed" data-embed-id="%s" data-embed-element="aside" ]}%%', $url);
                        } else {
                            $items[] = $markup;
                        }
                        break;
                    case 'embedded_instagram_card':
                        $markup = $this->resolveDotNotation($itemArray, sprintf('field_instagram_card_html_markup.%s.0.value', $language));
                        if (preg_match_all('/src="(.+?)"/i', $markup, $matches) > 0) {
                            $url = array_pop($matches[1]);
                            $items[] = sprintf('%%{[ data-embed-type="oembed" data-embed-id="%s" data-embed-element="aside" ]}%%', $url);
                        } else {
                            $items[] = $markup;
                        }
                        break;
                    case 'sidebar':
                        $caption = $this->resolveDotNotation($itemArray, sprintf('field_embedded_text.%s.0.value', $language));
                        $image = $this->resolveDotNotation($itemArray, sprintf('field_image.%s.0', $language));
                        if ($image) {
                            $fp = $this->createImage($image, $caption);
                            $this->pushImageRef($node, $fp);
                            $items[] = sprintf('<div class="embedded-image"><img src="%s" data-align="right" /></div>', $fp);
                        } else {
                            $node->sidebars[] = $caption;
                        }
                        break;
                    case 'embedded_storify_widget':
                        $markup = $this->resolveDotNotation($itemArray, sprintf('field_embed_code.%s.0.value', $language));
                        $items[] = $markup;
                        break;
                    case 'embedded_photo_gallery':
                        $gallery = $this->resolveDotNotation($itemArray, sprintf('field_embedded_gallery.%s', $language));
                        if (!$gallery) break;
                        foreach ($gallery as $image) {
                            if (!$image) continue;
                            $caption = $this->resolveDotNotation($image, 'field_file_image_caption_text.%s.0.value');
                            $alt = $this->resolveDotNotation($image, 'field_file_image_alt_text.%s.0.value');
                            $title = $this->resolveDotNotation($image, 'field_file_image_title_text.%s.0.value');
                            $title = $image['title'] ? $image['title'] : $title;
                            $fp = $this->createImage($image);
                            $this->pushImageRef($node, $fp);
                            $items[] = sprintf('<div class="embedded-image"><img src="%s" class="embedded-image" title="%s" caption="%s" alt="%s" /></div>', $fp, $title, $caption, $alt);
                        }
                        break;
                    case 'downloadable_file':
                        $file = $this->resolveDotNotation($itemArray, sprintf('field_downloadable_file.%s.0', $language));
                        $description = $this->resolveDotNotation($itemArray, sprintf('field_file_description.%s.0.value', $language));
                        if ($file) {
                            $fp = $this->createAsset($file, 'Document', $description);
                            $node->legacy['refs']['assets'][$this->getKey()][] = $fp;
                            $items[] = sprintf('<div class="embedded-document"><a href="%s">%s</a></div>', $fp, $description);
                        }
                        break;
                    default:
                        var_dump($item, $node->_id);
                        throw new \Exception(sprintf('Unknown paragraph type %s', $item->bundle));
                }
            }
            $node->body = join("\n", $items);
        }
        unset($node->field_body_paragraphs);
    }

    protected function getLeadershipSection($legacyTermId)
    {
        return $this->map['leadership']['map'][$legacyTermId];
    }

    protected function createScheduleRef(&$node, $legacyId, $sectionId, $startDate, $endDate = null)
    {
        $kv = [
            'legacy'    => [
                'id'        => $legacyId,
                'source'    => $this->getKey(),
            ],
            'section'   => $sectionId,
            'startDate' => $startDate,
        ];
        if ($endDate) $kv['endDate'] = $endDate;
        $node->legacy['refs']['schedules'][$this->getKey()][] = $kv;
    }

    protected function handleLeadershipScheduling(&$node, $printProfile)
    {
        // Force reset this so we don't keep creating duplicates on re-runs.
        $node->legacy['refs']['schedules'][$this->getKey()] = [];
        $company = json_decode(json_encode($node, 512), true);
        $printArr = json_decode(json_encode($printProfile, 512), true);
        $language = $node->language ? $node->language : 'und';

        $sessions = $this->resolveDotNotation($company, sprintf('field_ld_session.%s', $language));
        if (count($sessions)) {
            $years = array_filter(array_map(function ($o) { return (int) taxonomy_term_load($o['tid'])->name; }, $sessions));
            $terms = [];
            $termKeys = $this->map['leadership']['term_fields'] ? $this->map['leadership']['term_fields'] : ['categories'];
            $language = $printProfile->language ? $printProfile->language : 'und';
            foreach ($termKeys as $key) {
                $key = sprintf('field_ld_%s.%s', $key, $language);
                $tids = array_filter(array_map(function($o) {
                    $term = taxonomy_term_load($o['tid']);
                    if (!$term) return false;
                    return sprintf('%s_%s', $term->vid, $term->tid);
                }, $this->resolveDotNotation($printArr, $key, [])));
                $terms = array_merge($terms, $tids);
            }

            foreach ($years as $year) {
                $startDate = strtotime(sprintf('%s-01-01T00:00:00', $year));
                $endDate = strtotime(sprintf('%s-12-31T23:59:59', $year));
                foreach ($terms as $term) {
                    $sectionId = $this->getLeadershipSection($term);
                    if (!$sectionId) continue;
                    $legacyId = sprintf('%s_%s_%s', $node->nid, $term, $year);
                    $this->createScheduleRef($node, $legacyId, $sectionId, $startDate, $endDate);
                }
            }
        }
    }

    protected function handleLeadershipDataCard(&$node)
    {
        $dcid = leadership_get_corresponding_nid($node->nid, 'leadership_data_card', $this->getKey());
        if (!$dcid) return;

        $dc = node_load($dcid);
        $dcarr = json_decode(json_encode($dc, 512), true);
        $language = $dc->language ? $dc->language : 'und';

        $name = $this->resolveDotNotation($dcarr, sprintf('field_ld_contact.%s.0.value', $language));
        if ($name) {
            $name = trim($name);
            $kv = ['name'  => $name];

            $title = $this->resolveDotNotation($dcarr, sprintf('field_ld_contact_title.%s.0.value', $language));
            if ($title) $kv['title'] = $title;

            $photo = $this->resolveDotNotation($dcarr, sprintf('field_ld_contact_photo.%s.0', $language));
            if ($photo) {
                $fp = $this->createImage($photo);
                $kv['legacy']['refs']['images'][$this->getKey()][] = $fp;
                $kv['legacy']['refs']['primaryImage'][$this->getKey()] = $fp;
            }

            $this->importContact($kv);
            $node->legacy['refs']['publicContacts'][$this->getKey()][] = $name;
        }

        $values = $this->resolveDotNotation($dcarr, sprintf('field_ld_product_photo.%s', $language));
        if ($values) {
            foreach ($values as $value) {
                $fc = field_collection_field_get_entity($value);
                if (!$fc) continue;
                $fcArr = json_decode(json_encode($fc, 512), true);
                $url = $this->resolveDotNotation($fcArr, sprintf('field_ld_link.%s.0.value', $language));
                $url = 1 === preg_match('/^http/', $url) ? $url : sprintf('http://%s', $url);
                $kv = [
                    'name'      => sprintf('%s Product Photo', $node->title),
                    'linkUrl'   => $url,
                    'linkText'  => $this->resolveDotNotation($fcArr, sprintf('field_ld_photo_title.%s.0.value', $language)),
                    'legacy'    => [
                        'id'    => $fc->item_id,
                        'source' => $this->getKey(),
                        'refs'  => [
                            'company'   => [$this->getKey() => $node->legacy['id']],
                        ],
                    ],
                ];

                $image = $this->resolveDotNotation($fcArr, sprintf('field_ld_product_photos.%s.0', $language));
                if (!$image) $image = $this->resolveDotNotation($fcArr, sprintf('field_ld_prod_photos.%s.0', $language));
                if ($image) {
                    $fp = $this->createImage($image);
                    $kv['legacy']['refs']['images'][$this->getKey()][] = $fp;
                    $kv['legacy']['refs']['primaryImage'][$this->getKey()] = $fp;
                }

                $this->importPromotion($kv);
            }
        }

        $value = $this->resolveDotNotation($dcarr, sprintf('field_ld_teaser.%s.0.value', $language));
        if ($value) $node->teaser = $value;

        $this->convertFields($dc);
        $dcarr = json_decode(json_encode($dc, 512), true);

        $node->name = $this->resolveDotNotation($dcarr, 'name');
        $node->body = $this->resolveDotNotation($dcarr, 'body');

        foreach ($dcarr['mutations']['Website']['redirects'] as $redirect) {
            $node->mutations['Website']['redirects'][] = $redirect;
        }
    }

    protected function handleLeadershipOnlineProfile(&$node)
    {
        $opid = leadership_get_corresponding_nid($node->nid, 'leadership_online_profile', $this->getKey());
        if (!$opid) return;

        $op = node_load($opid);
        $oparr = json_decode(json_encode($op, 512), true);
        $language = $op->language ? $op->language : 'und';

        $socialFields = ['facebook', 'linkedin', 'pinterest', 'twitter', 'youtube'];
        foreach ($socialFields as $field) {
            $value = $this->resolveDotNotation($oparr, sprintf('field_ld_%s.%s.0.url', $field, $language));
            if ($value) {
                $url = 1 === preg_match('/^http/', $value) ? $value : sprintf('https://%s', $value);
                $node->socialLinks[] = [
                    'provider'  => $field,
                    'label'     => ucfirst($field),
                    'url'       => $url,
                ];
            }
        }

        $value = $this->resolveDotNotation($oparr, sprintf('field_youtube_username.%s.0.value', $language));
        if ($value) {
            $override = $this->resolveDotNotation($oparr, sprintf('field_youtube_username_override.%s.0.value', $language), false);
            $template = $override ? 'https://youtube.com/channel/%s' : 'https://youtube.com/%s';
            $url = sprintf($template, $value);
            $node->socialLinks[] = [
                'provider'  => 'youtube',
                'label'     => 'Youtube',
                'url'       => $url,
            ];
        }

        $value = $this->resolveDotNotation($oparr, sprintf('field_youtube_video.%s.0.input', $language));
        if ($value) {
            // @todo this should be an embedded video
            $url = 1 === preg_match('/^http/', $value) ? $value : sprintf('https://%s', $value);
            $node->youtubeVideo = $url;
        }

        $value = $this->resolveDotNotation($oparr, sprintf('field_ld_logo.%s.0', $language));
        if ($value) {
            $extra = [
                'isLogo'    => true,
                'mutations' => [
                    'Website'   => [
                        'active'    => true,
                    ],
                ],
            ];
            $fp = $this->createImage($value, null, null, $extra);
            $this->pushImageRef($node, $fp);
        }

        $value = $this->resolveDotNotation($oparr, sprintf('field_ld_employees.%s.0.value', $language));
        if ($value) $node->numberOfEmployees = $value;

        $value = $this->resolveDotNotation($oparr, sprintf('field_ld_training.%s.0.value', $language));
        if ($value) $node->trainingInformation = $value;

        $value = $this->resolveDotNotation($oparr, sprintf('field_ld_years.%s.0.value', $language));
        if ($value) $node->yearsInOperation = $value;

        $value = $this->resolveDotNotation($oparr, sprintf('field_ld_geo_distrib.%s.0.value', $language));
        if ($value) $node->salesRegion = $value;

        $value = $this->resolveDotNotation($oparr, sprintf('field_ld_services.%s.0.value', $language));
        if ($value) $node->servicesProvided = $value;

        $values = $this->resolveDotNotation($oparr, sprintf('field_ld_sales.%s', $language));
        if ($values) $node->salesChannels = join(', ', array_map(function ($v) { return $v['value']; }, $values));

        $this->convertFields($op);
        $oparr = json_decode(json_encode($op, 512), true);

        $value = $this->resolveDotNotation($oparr, 'name');
        if ($value) $node->name = $value;

        $value = $this->resolveDotNotation($oparr, 'body');
        if ($value) $node->body = $value;

        foreach ($oparr['mutations']['Website']['redirects'] as $redirect) {
            $node->mutations['Website']['redirects'][] = $redirect;
        }
    }

    protected function handleLeadershipPrintProfile(&$node)
    {
        $ppid = leadership_get_corresponding_nid($node->nid, 'leadership_print_profile', $this->getKey());
        if (!$ppid) return;

        $pp = node_load($ppid);
        $this->handleLeadershipScheduling($node, $pp);

        $pparr = json_decode(json_encode($pp, 512), true);
        $language = $pp->language ? $pp->language : 'und';

        $value = $this->resolveDotNotation($pparr, sprintf('field_ld_address_1.%s.0.value', $language));
        if ($value) $node->address1 = $value;

        $value = $this->resolveDotNotation($pparr, sprintf('field_ld_address_2.%s.0.value', $language));
        if ($value) $node->address2 = $value;

        $value = $this->resolveDotNotation($pparr, sprintf('field_ld_address_city.%s.0.value', $language));
        if ($value) $node->city = $value;

        $value = $this->resolveDotNotation($pparr, sprintf('field_ld_state.%s.0.value', $language));
        if ($value) $node->state = $value;

        $value = $this->resolveDotNotation($pparr, sprintf('field_ld_zip_postal_code.%s.0.value', $language));
        if ($value) $node->zip = $value;

        $value = $this->resolveDotNotation($pparr, sprintf('field_ld_country.%s.0.value', $language));
        if ($value) $node->country = $value;

        $value = $this->resolveDotNotation($pparr, sprintf('field_ld_email.%s.0.value', $language));
        if ($value) $node->publicEmail = $value;

        $value = $this->resolveDotNotation($pparr, sprintf('field_ld_fax.%s.0.value', $language));
        if ($value) $node->fax = $value;

        $value = $this->resolveDotNotation($pparr, sprintf('field_ld_phone.%s.0.value', $language));
        if ($value) $node->phone = $value;

        $value = $this->resolveDotNotation($pparr, sprintf('field_ld_website.%s.0.value', $language));
        if ($value) $node->website = $value;

        $value = $this->resolveDotNotation($pparr, sprintf('field_ld_print_logo.%s.0', $language));
        if ($value) {
            $extra = [
                'mutations' => [
                    'Magazine'   => [
                        'active'    => true,
                    ],
                ],
            ];
            $fp = $this->createImage($value, null, null, $extra);
            $this->pushImageRef($node, $fp);
        }

        $value = $this->resolveDotNotation($pparr, sprintf('field_ld_print_photo_1.%s.0', $language));
        if ($value) {
            $extra = [
                'mutations' => [
                    'Magazine'   => [
                        'active'    => true,
                    ],
                ],
            ];
            $fp = $this->createImage($value, null, null, $extra);
            $this->pushImageRef($node, $fp);
        }

        $value = $this->resolveDotNotation($pparr, sprintf('field_ld_print_photo_2.%s.0', $language));
        if ($value) {
            $extra = [
                'mutations' => [
                    'Magazine'   => [
                        'active'    => true,
                    ],
                ],
            ];
            $fp = $this->createImage($value, null, null, $extra);
            $this->pushImageRef($node, $fp);
        }

        $this->convertFields($pp);
        $pparr = json_decode(json_encode($pp, 512), true);

        $value = $this->resolveDotNotation($pparr, 'name');
        if ($value) $node->mutations['Magazine']['name'] = $value;

        $value = $this->resolveDotNotation($pparr, 'body');
        if ($value) $node->mutations['Magazine']['body'] = $value;

        foreach ($pparr['mutations']['Website']['redirects'] as $redirect) {
            $node->mutations['Website']['redirects'][] = $redirect;
        }
    }

    protected function handleLeadershipData(&$node)
    {
        if ($node->type !== 'company' || !function_exists('leadership_get_corresponding_nid')) return;
        $node->socialLinks = [];

        $this->handleLeadershipDataCard($node);
        $this->handleLeadershipOnlineProfile($node);
        $this->handleLeadershipPrintProfile($node);
    }

    /**
     * {@inheritdoc}
     *
     * Reformats node data into consumable fields
     */
    protected function convertFields(&$node)
    {

        $nodeArray = json_decode(json_encode($node, 512), true);
        $language = $node->language ? $node->language : 'und';

        // @jpdev - move this some place earlier to apply to entire modifier at start
        $node->legacy['raw'] =  $nodeArray;

        //$origNode = clone ($node);
        //$origNode = json_decode(json_encode($origNode, 512), true);
        //$node->legacy['raw'] =  $origNode;

        // @jpdev - maintaining _id may be a bad idea
        $node->_id = (int) $node->nid;

        $node->type = str_replace('Platform\\Content\\', '', $this->map['Content'][$node->type]);

        // @jpdev - are our status bit values consistent with theirs?
        $node->status = (int) $node->status;

        $node->name = $node->title;

        // dates
        $node->created = (int) $node->created;
        $node->updated = (int) $node->changed;
        $node->published = strtotime($this->resolveDotNotation($nodeArray, sprintf('field_penton_published_datetime.%s.0.value', $language)));

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
        $node->mutations['Website']['redirects'] = $redirects;

        // refs
        $node->legacy['refs']['createdBy'][$this->getKey()] = $node->uid;
        $node->legacy['refs']['updatedBy'][$this->getKey()] = $node->revision_uid;

        // Images (Adds image to 'Image' object to middile db, also adds legacy.ref to node for linking after b4 Asset creation)
        $this->convertImages($node, $nodeArray);





        $this->removeCrapFields($node);


        // just to test
        //$node = $newNode;
        /*
        // removed leadership call
        // removed content type defined by taxonomy code
        // removed legacy.refs.authors reset (why was it in there?)
        // removed unpublished field (none seen in source)





        // body
        $body = $this->resolveDotNotation($nodeArray, sprintf('body.%s.0.value', $language));
        $node->body = $body;
        if (empty($body)) {
            unset($node->body);
        }

        $paragraphs = $this->resolveDotNotation($nodeArray, sprintf('field_body_paragraphs.%s', $language));
        $this->handleParagraphs($node, $paragraphs);

        // teaser
        $teaser = $this->resolveDotNotation($nodeArray, sprintf('field_deckhead.%s.0.value', $language));
        if (!empty($teaser)) $node->teaser = $teaser;
        unset($node->field_deckhead);

        $teaser = $this->resolveDotNotation($nodeArray, sprintf('field_summary.%s.0.value', $language));
        if (!empty($teaser)) $node->teaser = $teaser;
        unset($node->field_summary);

        $author = $this->resolveDotNotation($nodeArray, sprintf('field_byline.%s.0.value', $language));
        // if (!$author) $author = $this->resolveDotNotation($nodeArray, 'name');
        if ($author) {
            list($first, $last) = explode(' ', $author, 2);
            $title = $this->resolveDotNotation($nodeArray, sprintf('field_author_title.%s.0.value', $language));
            $this->importContact([
                'firstName' => $first,
                'lastName' => $last,
                'title' => $title,
            ]);
            $node->legacy['refs']['authors'][$this->getKey()][] = trim($author);
        }

        $companies = $this->resolveDotNotation($nodeArray, sprintf('field_companies.%s', $language));
        if (!empty($companies)) {
            foreach ($companies as $ref) {
                $node->legacy['refs']['companies'][$this->getKey()][] = $ref['nid'];
            }
        }
        unset($node->field_companies);

        // Taxonomy
        $taxFields = ['coverage', 'source_type', 'coverage_type', 'column_type', 'subtype', 'company_type'];
        // @todo check field_term_vocab, field_term_vocab_primary_industry, field_allterms
        foreach ($taxFields as $type) {
            $refs = $this->resolveDotNotation($nodeArray, sprintf('field_term_%s.%s', $type, $language));
            if ($refs) {
                foreach ($refs as $ref) {
                    $term = taxonomy_term_load($ref['tid']);
                    $id = sprintf('%s_%s', $term->vid, $ref['tid']);
                    $node->legacy['refs']['taxonomy'][$this->getKey()][] = $id;
                }
            }
            unset($node->{sprintf('field_term_%s', $type)});
        }

        // Set company type field
        $value = $this->resolveDotNotation($nodeArray, sprintf('field_term_company_type.%s.0', $language));
        if ($value) {
            $term = taxonomy_term_load($value['tid']);
            if ($term) $node->companyType = $term->name;
        }

        $allTerms = $this->resolveDotNotation($nodeArray, sprintf('field_allterms.%s', $language));
        if (!empty($allTerms)) {
            foreach ($allTerms as $ref) {
                $term = taxonomy_term_load($ref['tid']);
                $id = sprintf('%s_%s', $term->vid, $ref['tid']);
                $node->legacy['refs']['taxonomy'][$this->getKey()][] = $id;
            }
        }
        unset($node->field_allterms);

        // Related To
        $relatedTo = $this->resolveDotNotation($nodeArray, sprintf('field_related.%s', $language));
        if (!empty($relatedTo)) {
            foreach ($relatedTo as $ref) {
                $node->legacy['refs']['relatedTo'][$this->getKey()][] = $ref['nid'];
            }
        }
        unset($node->field_related);

        // Address data
        $address1 = $this->resolveDotNotation($nodeArray, sprintf('field_address1.%s.value', $language));
        if ($address1) $node->address1 = $address1;
        unset($node->field_address1);

        $address1 = $this->resolveDotNotation($nodeArray, sprintf('field_street.%s.0.value', $language));
        if ($address1) $node->address1 = $address1;
        unset($node->field_street);

        $address2 = $this->resolveDotNotation($nodeArray, sprintf('field_address2.%s.value', $language));
        if ($address2) $node->address1 = $address2;
        unset($node->field_address2);

        $address2 = $this->resolveDotNotation($nodeArray, sprintf('field_addr2.%s.value', $language));
        if ($address2) $node->address1 = $address2;
        unset($node->field_addr2);

        $city = $this->resolveDotNotation($nodeArray, sprintf('field_city.%s.value', $language));
        if ($city) $node->city = $city;
        unset($node->field_city);

        $country = $this->resolveDotNotation($nodeArray, sprintf('field_country.%s.value', $language));
        if ($country) $node->country = $country;
        $country = $this->resolveDotNotation($nodeArray, sprintf('field_country.%s.0.value', $language));
        if ($country) $node->country = $country;
        unset($node->field_country);

        $zip = $this->resolveDotNotation($nodeArray, sprintf('field_zipcode.%s.value', $language));
        if ($zip) $node->zip = $zip;
        unset($node->field_zipcode);

        $zip = $this->resolveDotNotation($nodeArray, sprintf('field_zip.%s.0.value', $language));
        if ($zip) $node->zip = $zip;
        unset($node->field_zip);

        $state = $this->resolveDotNotation($nodeArray, sprintf('field_state.%s.value', $language));
        if ($state) $node->state = $state;
        $state = $this->resolveDotNotation($nodeArray, sprintf('field_state.%s.0.value', $language));
        if ($state) $node->state = $state;
        unset($node->field_state);

        $fax = $this->resolveDotNotation($nodeArray, sprintf('field_fax.%s.value', $language));
        if ($fax) $node->fax = $fax;
        unset($node->field_fax);

        $phone = $this->resolveDotNotation($nodeArray, sprintf('field_phone.%s.value', $language));
        if ($phone) $node->phone = $phone;
        unset($node->field_phone);

        $phone = $this->resolveDotNotation($nodeArray, sprintf('field_company_phone.%s.0.value', $language));
        if ($phone) $node->phone = $phone;
        unset($node->field_company_phone);

        // Sidebars
        $blockquote = $this->resolveDotNotation($nodeArray, sprintf('field_blockquote.%s.value', $language));
        if ($blockquote) {
            $node->sidebars[] = $blockquote;
        }
        unset($node->field_blockquote);

        // Podcasts
        $podcast = $this->resolveDotNotation($nodeArray, sprintf('field_podcast.%s', $language));
        if ($podcast) {
            $podcast['_uri'] = file_create_url($podcast['uri']);
            $node->legacy['refs']['files'][] = $podcast;
        }
        unset($node->field_podcast);

        // Some podcasts support `sub podcasts` -- additional files/tracks uploaded to the podcast. Base won't
        // These may need to be brought in as related content or something in the future.
        unset($node->field_sub_podcasts);


        // News
        // field_link.%s.{title,url} link to news source -- embed in content body?
        $company = $this->resolveDotNotation($nodeArray, sprintf('field_wir_sponsor.%s.target_id', $language));
        if ($company) {
            $node->legacy['refs']['company'] = (int) $company;
        }
        unset($node->field_wir_sponsor);

        // Videos
        // field_white_paper // Exist on Videos, contain youtube links??
        $viddler = $this->resolveDotNotation($nodeArray, sprintf('field_viddler_id.%s.0', $language));
        if ($viddler) $node->embedCode = $viddler['embed_code'];

        // Whitepapers

        // Document
        // field_top_copy
        // field_eyebrow
        $files = $this->resolveDotNotation($nodeArray, sprintf('field_download_document.%s', $language));
        if (!empty($files)) {
            foreach ($files as $file) {
                $file['_uri'] = file_create_url($file['uri']);
                $node->legacy['refs']['files'][] = $file;
            }
        }
        unset($node->field_download_document);

        $files = $this->resolveDotNotation($nodeArray, sprintf('field_content_pdf.%s', $language));
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
        $youtube = $this->resolveDotNotation($nodeArray, sprintf('field_youtube_username.%s.value', $language));
        if ($youtube) $node->socialLinks[] = [
            'provider'  => 'youtube',
            'label'     => 'Youtube',
            'url'       => sprintf('https://youtube.com/%s', $youtube)
        ];
        unset($node->field_youtube_username);

        $value = $this->resolveDotNotation($nodeArray, sprintf('field_logo.%s', $language));
        if ($value) {
            $extra = [
                'isLogo'    => true,
                'mutations' => [
                    'Website'   => [
                        'active'    => true,
                    ],
                ],
            ];
            $fp = $this->createImage($value, null, null, $extra);
            $this->pushImageRef($node, $fp);
        }
        unset($node->field_logo);

        $link = $this->resolveDotNotation($nodeArray, sprintf('field_link.%s.url', $language));
        if ($link) $node->website = $link;
        unset($node->field_link);

        $link = $this->resolveDotNotation($nodeArray, sprintf('field_website.%s.0.url', $language));
        if ($link) $node->website = $link;
        unset($node->field_website);

        // Leadership Session
        $tid = $this->resolveDotNotation($nodeArray, sprintf('field_ld_session.%s.tid', $language));
        if ($tid) {
            $term = taxonomy_term_load($tid);
            $id = sprintf('%s_%s', $term->vid, $tid);
            $node->legacy['refs']['taxonomy'][$this->getKey()][] = $id;
        }
        unset($node->field_ld_session);

        $date = $this->resolveDotNotation($nodeArray, sprintf('field_event_date.%s.0.value', $language));
        if ($date) {
            $node->startDate = date('c', strtotime($date));
            $end = $this->resolveDotNotation($nodeArray, sprintf('field_event_date.%s.0.value2', $language));
            if ($end) {
                $node->endDate = date('c', strtotime($end));
            } else {
                $node->allDay = true;
            }
        }
        unset($node->field_event_date);

        $date = $this->resolveDotNotation($nodeArray, sprintf('field_date.%s.0.value', $language));
        if ($date) {
            $node->startDate = date('c', strtotime($date));
            $end = $this->resolveDotNotation($nodeArray, sprintf('field_date.%s.0.value2', $language));
            if ($end) {
                $node->endDate = date('c', strtotime($end));
            } else {
                $node->allDay = true;
            }
        }
        unset($node->field_date);

        $byline = $this->resolveDotNotation($nodeArray, sprintf('field_contributed_author.%s.0.value', $language));
        if ($byline) $node->byline = $byline;
        unset($node->field_contributed_author);

        $authors = $this->resolveDotNotation($nodeArray, sprintf('field_staff_author.%s', $language));
        if (!empty($authors)) {
            foreach ($authors as $author) {
                $node->legacy['refs']['authors'][$this->getKey()][] = $author['nid'];
            }
        }


        if ($this->getKey() === 'id') {
            // Change the one stupid "podcast" on id to an article
            if ($node->_id === 20611) $node->type = 'Article';
        }

        $linkUrl = $this->resolveDotNotation($nodeArray, sprintf('field_texterity_url.%s.0.url', $language));
        if ($linkUrl) $node->linkUrl = $linkUrl;
        unset($node->field_texterity_url);

        $email = $this->resolveDotNotation($nodeArray, sprintf('field_email_address.%s.0.email', $language));
        if ($email) $node->publicEmail = $email;
        if ($email) $node->email = $email;
        unset($node->field_email_address);

        $jobTitle = $this->resolveDotNotation($nodeArray, sprintf('field_job_title.%s.0.value', $language));
        if ($jobTitle) $node->title = $jobTitle;
        unset($node->field_job_title);

        $phone = $this->resolveDotNotation($nodeArray, sprintf('field_phone_number.%s.0.value', $language));
        if ($phone) $node->phone = $phone;
        unset($node->field_phone_number);

        $twitter = $this->resolveDotNotation($nodeArray, sprintf('field_twitter_handle.%s.0.value', $language));
        if ($twitter) $node->socialLinks[] = [
            'provider'  => 'twitter',
            'label'     => 'Twitter',
            'url'       => sprintf('https://twitter.com/%s', str_replace('@', '', $twitter))
        ];
        unset($node->field_twitter_handle);

        if ($node->type === 'Contact') {
            list($firstName, $lastName) = explode(' ', $node->name, 2);
            $node->firstName = $firstName;
            $node->lastName = $lastName;
        }

        $company = $this->resolveDotNotation($nodeArray, sprintf('field_company_profile_reference.%s.0.nid', $language));
        if ($company) $node->legacy['refs']['company'][$this->getKey()] = $company;
        unset($node->field_company_profile_reference);

        $cta = $this->resolveDotNotation($nodeArray, sprintf('field_call_to_action_link.%s.0', $language));
        if ($cta) {
            $node->linkText = $cta['title'];
            $node->linkUrl = $cta['url'];
        }
        unset($node->field_call_to_action_link);

        $body = $this->resolveDotNotation($nodeArray, sprintf('field_copy_text.%s.0.value', $language));
        if ($body) $node->body = $body;
        unset($node->field_copy_text);

        $teaser = $this->resolveDotNotation($nodeArray, sprintf('field_headline_text.%s.0.value', $language));
        if ($teaser) $node->teaser = $teaser;
        unset($node->field_headline_text);

        $status = $this->resolveDotNotation($nodeArray, sprintf('field_ad_status.%s.0.value', $language));
        if ($status !== null) $node->status = (int) $status;

        $date = $this->resolveDotNotation($nodeArray, sprintf('field_ad_dates.%s.0.value', $language));
        if ($date) {
            $node->published = date('c', strtotime($date));
            $end = $this->resolveDotNotation($nodeArray, sprintf('field_ad_dates.%s.0.value2', $language));
            if ($end) {
                $node->unpublished = date('c', strtotime($end));
            }
        }
        unset($node->field_ad_dates);

        $whitepaper = $this->resolveDotNotation($nodeArray, sprintf('field_whitepaper.%s.0.nid', $language));
        if ($whitepaper) $node->legacy['refs']['relatedTo'][$this->getKey()][] = $whitepaper;
        unset($node->field_whitepaper);

        $url = $this->resolveDotNotation($nodeArray, sprintf('field_brightcove_url.%s.0.url', $language));
        if ($url) $node->embedCode = sprintf('<iframe src="%s"></iframe>', $url);
        unset($node->field_brightcove_url);

        $file = $this->resolveDotNotation($nodeArray, sprintf('field_product_data_sheet.%s.0', $language));
        if ($file) {
            $fp = $this->createAsset($file);
            $node->legacy['refs']['relatedTo'][$this->getKey()][] = $fp;
        }
        unset($node->field_product_data_sheet);

        $url = $this->resolveDotNotation($nodeArray, sprintf('field_website_deep_link.%s.0.url', $language));
        if ($url) $node->website = $url;
        unset($node->field_website_deep_link);

        $partNumber = $this->resolveDotNotation($nodeArray, sprintf('field_product_part_number.%s.0.value', $language));
        if ($partNumber) $node->partNumber = $partNumber;
        unset($node->field_product_part_number);

        $msrp = $this->resolveDotNotation($nodeArray, sprintf('field_list_price.%s.0.value', $language));
        if ($msrp) $node->msrp = $msrp;
        unset($node->field_list_price);

        $url = $this->resolveDotNotation($nodeArray, sprintf('field_unbounce_url.%s.0.url', $language));
        if ($url) {
            $field = (in_array($node->type, ['Webinar', 'Whitepaper', 'Promotion'])) ? 'linkUrl' : 'website';
            $node->{$field} = $url;
        }
        unset($node->field_unbounce_url);

        $body = $this->resolveDotNotation($nodeArray, sprintf('field_whitepaper_full_text.%s.0.value', $language));
        if ($body) $node->body = $body;
        unset($node->field_whitepaper_full_text);

        $file = $this->resolveDotNotation($nodeArray, sprintf('field_lead_gen_file.%s.0', $language));
        if ($file) {
            $fp = $this->createAsset($file);
            $node->legacy['refs']['relatedTo'][$this->getKey()][] = $fp;
        }
        unset($node->field_lead_gen_file);


        */

        // DEBUG TESTING
        $ok = [
            // 'News',
        ];
        // if (!in_array($node->type, $ok)) {
        //     var_dump($node);
        //     die(__METHOD__);
        // }

    }

    protected function createAsset($file, $type = 'Document', $title = null)
    {
        if (!$file) throw new \InvalidArgumentException('Unable to process file!');

        $id = (int) $file['fid'];
        if ($id === 0) return;
        $url = file_create_url($file['uri']);
        $url = str_replace('http://default', $this->map['uri'], $url);
        $name = $file['filename'];
        $date = date('c', $file['timestamp']);

        $newName = sprintf('%s_%s_%s', $this->getKey(), $id, $name);

        $kv = [
            '_id'       => $id,
            'type'      => $type,
            'name'      => $title ? $title : $file['description'],
            'fileName'  => $newName,
            'source'    => [
                'location'  => $url,
                'name'      => $name,
            ],
            'legacy'    => [
                'id'        => $id,
                'source'    => sprintf($this->getKey()),
                'created'   => $date,
            ]
        ];

        $filter = ['_id' => $id];
        $update = ['$set' => $kv];
        $this->dbal->upsert($this->database, 'Asset', $filter, $update);
        return $url;
    }


    /**
     * {@inheritdoc}
     *
     * Creates an image to create as Asset in base4
     *
     */
    protected function createImage($img, $caption = null, $date = null, $extra = [])
    {
        var_dump(__method__);
        if (!$img) throw new \InvalidArgumentException('Unable to process image!');
        if (isset($img['thumbnail_fid'])) {
            $id = (int) $img['thumbnail_fid'];
            $url = $img['thumbnail_url'];
            $date = $date ? date('c', $date) : date('c');
        } else {
            $id = (int) $img['fid'];
            if ($id === 0) return;
            $url = file_create_url($img['uri']);
            $url = str_replace('http://default', $this->map['uri'], $url);
            $date = date('c', $img['timestamp']);
        }

        $name = preg_replace("/[^A-Za-z0-9\._]/", '_', urldecode(basename($url)));
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
            'caption'   => $img['title'] ? $img['title'] : $caption,
            'alt'       => $img['alt'] ? $img['alt'] : $caption,
            'legacy'    => [
                'id'        => $id,
                'source'    => sprintf($this->getKey()),
                'created'   => $date,
            ]
        ];

        foreach ($extra as $k => $v) {
            $kv[$k] = $v;
        }

        $filter = ['_id' => $id];
        $update = ['$set' => $kv];

        var_dump($update);

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
                    'name'  => sprintf('%s %s', strtoupper($this->getKey()), $vocab->name),
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
        if (!$contact['name']) {
            $contact['name'] = sprintf('%s %s', $contact['firstName'], $contact['lastName']);
        }
        $contact['name'] = trim($contact['name']);
        $contact['legacy']['id'] = $contact['name'];
        $contact['legacy']['source'] = $this->getKey();

        $filter = ['legacy.id' => $contact['name'], 'legacy.source' => $this->getKey()];
        $update = ['$set' => $contact];
        return $this->dbal->upsert($this->database, 'Content', $filter, $update);
    }

    /**
     * {@inheritdoc}
     *
     * Creates an image to create as Asset in base4
     *
     */
    protected function importPromotion($kv)
    {
        $kv['type'] = 'Promotion';
        $kv['legacy']['source'] = $this->getKey();

        $filter = ['legacy.id' => $kv['legacy']['id'], 'legacy.source' => $this->getKey()];
        $update = ['$set' => $kv];
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
        $language = $node->language ? $node->language : 'und';
        $nid = $node->nid;
        $issue = $node->field_magazine_issue;
        if (!empty($issue)) {
            $issueNid = $issue[$language][0]['nid'];
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
