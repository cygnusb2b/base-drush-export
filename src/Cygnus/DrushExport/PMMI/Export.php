<?php

namespace Cygnus\DrushExport\PMMI;

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
    /**
     * Main export function.
     */
    public function execute()
    {
        $this->writeln(sprintf('Starting import for %s', $this->key));

        // $this->importUsers();
        $this->importTaxonomies();
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
        $this->importMagazineIssueNodes();
        $this->importContentNodes();

        $this->outdent();
    }

    // strip fields so they do not show as unsupported, once identified as useless
    protected function removeCrapFields(&$node)
    {
        foreach ($this->map['structure']['stripFields'] AS $removeField) {
            unset($node->$removeField);
        }
        foreach ($node as $key => $value) {
            if (empty($value)) unset($node->{$key});
            if (stristr($key, 'field_')) unset($node->{$key});
        }
        foreach ($node->legacy['raw'] as $key => $value) {
            if (empty($value)) unset($node->legacy['raw'][$key]);
        }
        unset($node->legacy['raw']['data']);
        unset($node->legacy['raw']['rdf_mapping']);
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

            unset($node->field_image['und'][0]['metatags']);
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
                $set['description'] = $this->resolveDotNotation($nodeArray, 'body.und.0.value');
            }

            $issueDate = $this->resolveDotNotation($nodeArray, 'field_issue_date.und.0.value');
            $date = $this->resolveDotNotation($nodeArray, 'field_date.und.0.value');
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

            $tid = $this->resolveDotNotation($nodeArray, 'field_term_media.und.0.tid');
            if ($tid) {
                $term = taxonomy_term_load($tid);
                $set['legacy']['refs']['publication'] = ['tid' => $tid, 'vid' => $term->vid, 'name' => $term->name];
            }

            // coverImage
            $image = $this->resolveDotNotation($nodeArray, 'field_image.und.0');
            if (!$image) $image = $this->resolveDotNotation($nodeArray, 'field_magazine_image.und.0');
            if (!$image) $image = $this->resolveDotNotation($nodeArray, 'field_digital_cover_image.und.0');
            if ($image) {
                $fp = $this->createImage($image);
                $set['legacy']['refs']['coverImage'][$this->getKey()] = $fp;
            }

            // digital edition links
            $link = $this->resolveDotNotation($nodeArray, 'field_link.und.0.url');
            if (!$link) $link = $this->resolveDotNotation($nodeArray, 'field_texterity_url.und.0.url');
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
        if ($paragraphs) {
            $items = [];
            $ids = array_map(function($arr) { return $arr['value']; }, $paragraphs);
            foreach ($ids as $id) {
                $item = @paragraphs_item_load($id);
                if (!$item) continue;
                $itemArray = json_decode(json_encode($item, 512), true);
                switch ($item->bundle) {
                    case 'embedded_text':
                        $items[] = $this->resolveDotNotation($itemArray, 'field_embedded_text.und.0.value');
                        break;
                    case 'embedded_image':
                        $caption = $this->resolveDotNotation($itemArray, 'field_embedded_image_caption.und.0');
                        $image = $this->resolveDotNotation($itemArray, 'field_paragraphs_embedded_image.und.0');
                        if ($image) {
                            $fp = $this->createImage($image);
                            $this->pushImageRef($node, $fp);
                            $items[] = sprintf('<div class="embedded-image"><img src="%s" class="embedded-image" alt="%s" /></div>', $fp, $caption);
                        }
                        break;
                    case 'headshot_widget':
                        $author = $this->resolveDotNotation($itemArray, 'field_author.und.0');
                        $image = $this->resolveDotNotation($itemArray, 'field_image.und.0');
                        $jobTitle = $this->resolveDotNotation($itemArray, 'field_job_title.und.0');
                        $caption = $jobTitle ? sprintf('%s, %s', $author, $jobTitle) : $author;
                        if ($image) {
                            $fp = $this->createImage($image);
                            $this->pushImageRef($node, $fp);
                            $items[] = sprintf('<div class="embedded-image"><img src="%s" class="embedded-image" data-align="right" alt="%s" /></div>', $fp, $caption);
                        }
                        break;
                    case 'embedded_video':
                        $embed = $this->resolveDotNotation($itemArray, 'field_embedded_video_code.und.0.value');
                        if ($node->type === 'Video') {
                            $node->embedCode = $embed;
                        } else {
                            $items[] = sprintf('<div class="embedded-video">%s</div>', $embed);
                        }
                        break;
                    case 'embedded_twitter_card':
                        $markup = $this->resolveDotNotation($itemArray, 'field_twitter_card_html_markup.und.0.value');
                        if (preg_match_all('/href="(.+?)"/i', $markup, $matches) > 0) {
                            $url = array_pop($matches[1]);
                            $items[] = sprintf('%%{[ data-embed-type="oembed" data-embed-id="%s" data-embed-element="aside" ]}%%', $url);
                        } else {
                            $items[] = $markup;
                        }
                        break;
                    case 'embedded_instagram_card':
                        $markup = $this->resolveDotNotation($itemArray, 'field_instagram_card_html_markup.und.0.value');
                        if (preg_match_all('/src="(.+?)"/i', $markup, $matches) > 0) {
                            $url = array_pop($matches[1]);
                            $items[] = sprintf('%%{[ data-embed-type="oembed" data-embed-id="%s" data-embed-element="aside" ]}%%', $url);
                        } else {
                            $items[] = $markup;
                        }
                        break;
                    case 'sidebar':
                        $caption = $this->resolveDotNotation($itemArray, 'field_embedded_text.und.0');
                        $image = $this->resolveDotNotation($itemArray, 'field_image.und.0');
                        if ($image) {
                            $fp = $this->createImage($image);
                            $this->pushImageRef($node, $fp);
                            $items[] = sprintf('<div class="embedded-image"><img src="%s" class="embedded-image" data-align="right" alt="%s" /></div>', $fp, $caption);
                        } else {
                            $node->sidebars[] = $caption;
                        }
                        break;
                    case 'embedded_storify_widget':
                        $markup = $this->resolveDotNotation($itemArray, 'field_embed_code.und.0.value');
                        $items[] = $markup;
                        break;
                    case 'embedded_photo_gallery':
                        $gallery = $this->resolveDotNotation($itemArray, 'field_embedded_gallery.und');
                        if (!$gallery) break;
                        foreach ($gallery as $image) {
                            if (!$image) continue;
                            $caption = $this->resolveDotNotation($image, 'field_file_image_caption_text.und.0.value');
                            $alt = $this->resolveDotNotation($image, 'field_file_image_alt_text.und.0.value');
                            $title = $this->resolveDotNotation($image, 'field_file_image_title_text.und.0.value');
                            $title = $image['title'] ? $image['title'] : $title;
                            $fp = $this->createImage($image);
                            $this->pushImageRef($node, $fp);
                            $items[] = sprintf('<div class="embedded-image"><img src="%s" class="embedded-image" title="%s" caption="%s" alt="%s" /></div>', $fp, $title, $caption, $alt);
                        }
                        break;
                    case 'downloadable_file':
                        $file = $this->resolveDotNotation($itemArray, 'field_downloadable_file.und.0');
                        $description = $this->resolveDotNotation($itemArray, 'field_file_description.und.0');
                        if ($file) {
                            $fp = $this->createAsset($file, $description);
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
        if (!$tid) $tid = $this->resolveDotNotation($nodeArray, 'field_content_item_type.und.0.tid');
        if (!$tid) $tid = $this->resolveDotNotation($nodeArray, 'field_lead_gen_item_type.und.0.tid');
        if ($tid) {
            if (!isset($this->term_cache[$tid])) {
                $type = taxonomy_term_load($tid);
                $this->term_cache[$tid] = $type->name;
            }
            $type = $this->term_cache[$tid];

            if (in_array($type, ['News'])) $node->type = 'News';
            if (in_array($type, ['Podcast'])) $node->type = 'Podcast';
            if (in_array($type, ['Videos'])) $node->type = 'Video';
            if (in_array($type, ['Blog', 'Perspective', 'Column', 'Column/Opinion'])) $node->type = 'Blog';
            if (in_array($type, ['Industry Brief', 'Product Announcement', 'Controls Product Brief', 'Machine Product Brief', 'Materials Product Brief', 'Product Brief', 'Supplier News'])) $node->type = 'PressRelease';
            if (in_array($type, ['Webinar'])) $node->type = 'Webinar';
            if (in_array($type, ['White Paper', 'Case Study', 'Research Report', 'eBook', 'iReport'])) $node->type = 'Whitepaper';
            if (in_array($type, ['Infographic'])) $node->type = 'Promotion';
            if ($this->getKey() === 'id' && $nodeArray['type'] === 'lead_gen_item') {
                if ($type === 'Video') $node->type = 'Promotion';
            }
            // @todo review additional types for OEM, PFW, PW
            unset($node->field_term_subtype);
            unset($node->field_content_item_type);
        } else {
            if ($this->getKey() === 'id' && $nodeArray['type'] === 'lead_gen_item') {
                $node->type = 'Promotion';
            }
        }

        // _id
        $node->_id = (int) $node->nid;
        $node->name = $node->title;
        $node->status = (int) $node->status;

        $node->legacy['refs']['createdBy'][$this->getKey()] = $node->uid;
        $node->legacy['refs']['updatedBy'][$this->getKey()] = $node->revision_uid;

        $node->created = (int) $node->created;
        $node->updated = (int) $node->changed;
        $node->published = (int) $node->created;

        $unpublished = $this->resolveDotNotation($nodeArray, 'field_expiration_date.und.value');
        if ($unpublished) $node->unpublished = (int) $unpublished;
        unset($node->field_expiration_date);

        // Handle images
        $this->convertImages($node, $nodeArray);

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

        $paragraphs = $this->resolveDotNotation($nodeArray, 'field_body_paragraphs.und');
        $this->handleParagraphs($node, $paragraphs);

        // teaser
        $teaser = $this->resolveDotNotation($nodeArray, 'field_deckhead.und.0.value');
        if (!empty($teaser)) $node->teaser = $teaser;
        unset($node->field_deckhead);

        $teaser = $this->resolveDotNotation($nodeArray, 'field_summary.und.0.value');
        if (!empty($teaser)) $node->teaser = $teaser;
        unset($node->field_summary);

        $author = $this->resolveDotNotation($nodeArray, 'field_byline.und.0.value');
        if (!$author) $author = $this->resolveDotNotation($nodeArray, 'name');
        if ($author) {
            list($first, $last) = explode(' ', $author, 2);
            $title = $this->resolveDotNotation($nodeArray, 'field_author_title.und.0.value');
            $this->importContact([
                'firstName' => $first,
                'lastName' => $last,
                'title' => $title,
            ]);
            $node->legacy['refs']['authors'][$this->getKey()][] = trim($author);
        }


        $companies = $this->resolveDotNotation($nodeArray, 'field_companies.und');
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
            $refs = $this->resolveDotNotation($nodeArray, sprintf('field_term_%s.und', $type));
            if ($refs) {
                foreach ($refs as $ref) {
                    $term = taxonomy_term_load($ref['tid']);
                    $id = sprintf('%s_%s', $term->vid, $ref['tid']);
                    $node->legacy['refs']['taxonomy'][$this->getKey()][] = [
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
                $node->legacy['refs']['taxonomy'][$this->getKey()][] = [
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
                $node->legacy['refs']['relatedTo'][$this->getKey()][] = $ref['nid'];
            }
        }
        unset($node->field_related);

        // Address data
        $address1 = $this->resolveDotNotation($nodeArray, 'field_address1.und.value');
        if ($address1) $node->address1 = $address1;
        unset($node->field_address1);

        $address1 = $this->resolveDotNotation($nodeArray, 'field_street.und.0.value');
        if ($address1) $node->address1 = $address1;
        unset($node->field_street);

        $address2 = $this->resolveDotNotation($nodeArray, 'field_address2.und.value');
        if ($address2) $node->address1 = $address2;
        unset($node->field_address2);

        $address2 = $this->resolveDotNotation($nodeArray, 'field_addr2.und.value');
        if ($address2) $node->address1 = $address2;
        unset($node->field_addr2);

        $city = $this->resolveDotNotation($nodeArray, 'field_city.und.value');
        if ($city) $node->city = $city;
        unset($node->field_city);

        $country = $this->resolveDotNotation($nodeArray, 'field_country.und.value');
        if ($country) $node->country = $country;
        $country = $this->resolveDotNotation($nodeArray, 'field_country.und.0.value');
        if ($country) $node->country = $country;
        unset($node->field_country);

        $zip = $this->resolveDotNotation($nodeArray, 'field_zipcode.und.value');
        if ($zip) $node->zip = $zip;
        unset($node->field_zipcode);

        $zip = $this->resolveDotNotation($nodeArray, 'field_zip.und.0.value');
        if ($zip) $node->zip = $zip;
        unset($node->field_zip);

        $state = $this->resolveDotNotation($nodeArray, 'field_state.und.value');
        if ($state) $node->state = $state;
        $state = $this->resolveDotNotation($nodeArray, 'field_state.und.0.value');
        if ($state) $node->state = $state;
        unset($node->field_state);

        $fax = $this->resolveDotNotation($nodeArray, 'field_fax.und.value');
        if ($fax) $node->fax = $fax;
        unset($node->field_fax);

        $phone = $this->resolveDotNotation($nodeArray, 'field_phone.und.value');
        if ($phone) $node->phone = $phone;
        unset($node->field_phone);

        $phone = $this->resolveDotNotation($nodeArray, 'field_company_phone.und.0.value');
        if ($phone) $node->phone = $phone;
        unset($node->field_company_phone);

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
        if ($viddler) $node->embedCode = $viddler['embed_code'];

        // Whitepapers

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

        $link = $this->resolveDotNotation($nodeArray, 'field_website.und.0.url');
        if ($link) $node->website = $link;
        unset($node->field_website);

        // Leadership Session
        $tid = $this->resolveDotNotation($nodeArray, 'field_ld_session.und.tid');
        if ($tid) {
            $term = taxonomy_term_load($tid);
            $id = sprintf('%s_%s', $term->vid, $tid);
            $node->legacy['refs']['taxonomy'][$this->getKey()][] = [
                '$ref'  => 'Taxonomy',
                '$id'   => $id,
                '$db'   => 'drupal_pmmi_aw',
                'type'  => 'leadership_session',
            ];
        }
        unset($node->field_ld_session);

        $date = $this->resolveDotNotation($nodeArray, 'field_event_date.und.0.value');
        if ($date) {
            $node->startDate = date('c', strtotime($date));
            $end = $this->resolveDotNotation($nodeArray, 'field_event_date.und.0.value2');
            if ($end) {
                $node->endDate = date('c', strtotime($end));
            } else {
                $node->allDay = true;
            }
        }
        unset($node->field_event_date);

        $date = $this->resolveDotNotation($nodeArray, 'field_date.und.0.value');
        if ($date) {
            $node->startDate = date('c', strtotime($date));
            $end = $this->resolveDotNotation($nodeArray, 'field_date.und.0.value2');
            if ($end) {
                $node->endDate = date('c', strtotime($end));
            } else {
                $node->allDay = true;
            }
        }
        unset($node->field_date);

        $byline = $this->resolveDotNotation($nodeArray, 'field_contributed_author.und.0.value');
        if ($byline) $node->byline = $byline;
        unset($node->field_contributed_author);

        if ($this->getKey() === 'id') {
            // Change the one stupid "podcast" on id to an article
            if ($node->_id === 20611) $node->type = 'Article';
        }

        $linkUrl = $this->resolveDotNotation($nodeArray, 'field_texterity_url.und.0.url');
        if ($linkUrl) $node->linkUrl = $linkUrl;
        unset($node->field_texterity_url);

        $email = $this->resolveDotNotation($nodeArray, 'field_email_address.und.0.email');
        if ($email) $node->publicEmail = $email;
        if ($email) $node->email = $email;
        unset($node->field_email_address);

        $jobTitle = $this->resolveDotNotation($nodeArray, 'field_job_title.und.0.value');
        if ($jobTitle) $node->title = $jobTitle;
        unset($node->field_job_title);

        $phone = $this->resolveDotNotation($nodeArray, 'field_phone_number.und.0.value');
        if ($phone) $node->phone = $phone;
        unset($node->field_phone_number);

        $twitter = $this->resolveDotNotation($nodeArray, 'field_twitter_handle.und.0.value');
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

        $company = $this->resolveDotNotation($nodeArray, 'field_company_profile_reference.und.0.nid');
        if ($company) $node->legacy['refs']['company'][$this->getKey()] = $company;
        unset($node->field_company_profile_reference);

        $cta = $this->resolveDotNotation($nodeArray, 'field_call_to_action_link.und.0');
        if ($cta) {
            $node->linkText = $cta['title'];
            $node->linkUrl = $cta['url'];
        }
        unset($node->field_call_to_action_link);

        $body = $this->resolveDotNotation($nodeArray, 'field_copy_text.und.0.value');
        if ($body) $node->body = $body;
        unset($node->field_copy_text);

        $teaser = $this->resolveDotNotation($nodeArray, 'field_headline_text.und.0.value');
        if ($teaser) $node->teaser = $teaser;
        unset($node->field_headline_text);

        $status = $this->resolveDotNotation($nodeArray, 'field_ad_status.und.0.value');
        if ($status !== null) $node->status = (int) $status;

        $date = $this->resolveDotNotation($nodeArray, 'field_ad_dates.und.0.value');
        if ($date) {
            $node->published = date('c', strtotime($date));
            $end = $this->resolveDotNotation($nodeArray, 'field_ad_dates.und.0.value2');
            if ($end) {
                $node->unpublished = date('c', strtotime($end));
            }
        }
        unset($node->field_ad_dates);

        $whitepaper = $this->resolveDotNotation($nodeArray, 'field_whitepaper.und.0.nid');
        if ($whitepaper) $node->legacy['refs']['relatedTo'][$this->getKey()][] = $whitepaper;
        unset($node->field_whitepaper);

        $url = $this->resolveDotNotation($nodeArray, 'field_brightcove_url.und.0.url');
        if ($url) $node->embedCode = sprintf('<iframe src="%s"></iframe>', $url);
        unset($node->field_brightcove_url);

        $file = $this->resolveDotNotation($nodeArray, 'field_product_data_sheet.und.0');
        if ($file) {
            $fp = $this->createAsset($file);
            $node->legacy['refs']['relatedTo'][$this->getKey()][] = $fp;
        }
        unset($node->field_product_data_sheet);

        $url = $this->resolveDotNotation($nodeArray, 'field_website_deep_link.und.0.url');
        if ($url) $node->website = $url;
        unset($node->field_website_deep_link);

        $partNumber = $this->resolveDotNotation($nodeArray, 'field_product_part_number.und.0.value');
        if ($partNumber) $node->partNumber = $partNumber;
        unset($node->field_product_part_number);

        $msrp = $this->resolveDotNotation($nodeArray, 'field_list_price.und.0.value');
        if ($msrp) $node->msrp = $msrp;
        unset($node->field_list_price);

        $url = $this->resolveDotNotation($nodeArray, 'field_unbounce_url.und.0.url');
        if ($url) {
            $field = (in_array($node->type, ['Webinar', 'Whitepaper', 'Promotion'])) ? 'linkUrl' : 'website';
            $node->{$field} = $url;
        }
        unset($node->field_unbounce_url);

        $body = $this->resolveDotNotation($nodeArray, 'field_whitepaper_full_text.und.0.value');
        if ($body) $node->body = $body;
        unset($node->field_whitepaper_full_text);

        $file = $this->resolveDotNotation($nodeArray, 'field_lead_gen_file.und.0');
        if ($file) {
            $fp = $this->createAsset($file);
            $node->legacy['refs']['relatedTo'][$this->getKey()][] = $fp;
        }
        unset($node->field_lead_gen_file);

        $this->removeCrapFields($node);


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
    protected function createImage($img, $caption = null, $date = null)
    {
        if (!$img) throw new \InvalidArgumentException('Unable to process image!');
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
                'source'    => sprintf($this->getKey()),
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
        $contact['name'] = sprintf('%s %s', $contact['firstName'], $contact['lastName']);
        $contact['legacy'] = ['id' => $contact['name'], 'source' => $this->getKey()];

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
