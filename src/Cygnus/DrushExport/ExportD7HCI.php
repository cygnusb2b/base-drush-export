<?php

namespace Cygnus\DrushExport;

use DateTimeZone;
use DateTime;

/**
 * Provides support for exporting from Drupal 7
 *
 * Needs working (as in you can login to admin) site for drush to work
 * from drush repo root: php -d phar.readonly=0 compile
 * from drupal site root (ie /sites/heathcare-informatics/): drush scr $pathToPhar $mongoIp $importConfigKey
 * ie: drush scr /home/ec2-user/environment/base-drush-export-master/build/export.phar 10.0.12.156 hci
 *
 */
class ExportD7HCI extends Export
{
    /**
     * {@inheritdoc}
     */
    protected $configs = [
        'hci'       => [
            'Taxonomy'  => [
                'Policy & Payment'                      => 'Taxonomy\\Category',
                'Population Health & Data Analytics'    => 'Taxonomy\\Category',
                'Cybersecurity & Privacy'               => 'Taxonomy\\Category',
                'Clinical IT'                           => 'Taxonomy\\Category',
                'Business & Revenue Cycle Management'   => 'Taxonomy\\Category',
                'Tech Innovation'                       => 'Taxonomy\\Category',
                'Interoperability & HIE'                => 'Taxonomy\\Category',
                'Medical IT'                            => 'Taxonomy\\Category',
                'Topics Of Interest'                    => 'Taxonomy\\Topic',
                'Keyword Topics'                        => 'Taxonomy\\Tag',
                'HCI 100'                               => 'Taxonomy\\Category',
                'Multimedia'                            => 'Taxonomy\\Tag',
                'HIT Summit'                            => 'Taxonomy\\Tag',
                'iTunes Category'                       => 'Taxonomy\\Topic',
                'Section'                               => 'Taxonomy\\Category',
                'Ask The Editor'                        => 'Taxonomy\\Topic',
                'Content Alerts'                        => 'Taxonomy\\Tag',
                'eNews'                                 => 'Taxonomy\\Tag',
                '3rd Party Content'                     => 'Taxonomy\\Tag'
            ],
            'Content'   => [
                'article' => 'Website\\Content\\Article',
                'blog' => 'Website\\Content\\Blog',
                'faq' => 'Website\\Content\\Article',
                'hci100' => 'Website\\Content\\Top100',  // LOTS of additional fields
                'hci_iht2_marketing_solutions' => 'Website\\Content\\Article',
                'innovator' => 'Website\\Content\\Article',
                //'magazine_issue ' => 'Magazine\\Issue',
                'marketing_solutions' => 'Website\\Content\\Article', // Mainly from 2014, only 8 since 9/9/2014
                'media_kit' => 'Website\\Content\\Article', // Only about 24 items, all from 2014 and seem to all redirect to same page atm: https://www.healthcare-informatics.com/hci-iht2-marketing-solutions/about-hci-iht2
                'news' => 'Website\\Content\\News',
                //'page' => 'Website\\Content\\Page',  // earlier drupal sites we did as sections, but this is 'about us' and other static pages, so do differently here?
                //'page2' => 'Website\\Content\\Page',
                //'panel' => 'Website\\Content\\Page',
                //'poll' => 'Website\\Content\\Poll',  // no support in base4
                'prospectus' => 'Website\\Content\\Article', // 5 items from 2012-2013
                'research' => 'Website\\Content\\Article', // 4 items from 2014
                'summit' => 'Website\\Content\\Article', // 45 items from 2014 (non 'publshed' atm)
                'video' => 'Website\\Content\\Video',
                'webform' => 'Website\\Content\\Article', // 69 total, 2 from 2018, 0:2017, 9:2016.  2018 ones edited to say 'now closed', has body but also custom form element and submission - not supported in base4
                'webinar' => 'Website\\Content\\Webinar',  // look to have reg form in body.  admin talks about on24 but body content in admin does not match display (template?) http://ihealthtran.hs-sites.com/can-informatics-drive-clinical-quality-improvements-alongside-operational-improvements-in-cancer-care
                'whitepaper' => 'Website\\Content\\Whitepaper',
                'zebra' => 'Website\\Content\\Article',  // no content
                'contributor' => 'Website\\Content\\Contact', // not seen in admin, but seen as node 'type' from drush - will have to add support
                'newsgen_edition' => 'Website\\Content\\News', // no idea, only seen in drush @jpdev - investigate
                'newsgen_newsletter' => 'Website\\Content\\News', // no idea, only seen in drush @jpdev - investigate
            ],
            'Section'   => [
                'page' => 'Website\\Section',
                'page2' => 'Website\\Section',
                'panel' => 'Website\\Section',  
            ],
            'Issue'     => [
                'magazine_issue'         => 'Magazine\\Issue',
            ],
            'database'          => 'drupal_ebm_hci',
            'structure' =>  [
                'stripFields'   => [
                    'vid',
                    'log',
                    'tnid',
                    'cid', // comment id - prob not needed but search data to confirm
                    'translate',
                    'print_html_display',
                    'print_html_display_comment',
                    'print_html_display_urllist',
                    'last_comment_uid',
                    'last_comment_name',
                    'last_comment_timestamp',
                    'revision_timestamp', // looks to be same as 'changed' which we are using
                    'language' // use for determining path to text content, need to keep?
                    //'comment','promote, 'sticky','premium_content','comment_count','picture','data'  // other fields I see but leaving for now
                    //'moderate','format','feed','field_priority','field_leagcy_id','rdf_mapping','metatags','field_legacy_article_id');  // fields in old code, but not seen so far in hci
                ],
                '_id'       => 'nid',
                'name'      => 'title',
                'status'    => 'status',
                'createdBy' => 'uid',
                'updatedBy' => 'revision_uid',  // was using uid for both but I changed - ok, or less reliable?  @jpdev
                'created'   => 'created',
                'updated'   => 'changed',
                'published' => 'changed',
                'mutations' => 'path',
                'body'      => 'body.und.0.value',
                'teaser'    => 'field_teaser.und.0.value',
                'authors'   => 'field_byline.und.0.value',
                'deck'      => 'field_deck.und.0.value',
                'images'    => 'field_image.und',
                // existing fields (used mainly by top100)
                'phone'     => 'field_phone.und.0.value',
                'city'      => 'field_hci100_location.und.0.value',
                'website'   => 'field_website.und.0.value',
                'socialLinks'       => ['field_twitter.und.0.value', '	field_linkedin.und.0.value', 'field_facebook.und.0.value'],
                //'taxonomy'  => 'taxonomy',  // moving taxonomy refs in to legacy.refs from the outset, so not needed here (unless I move convertTaxonomy code into the convertFields method, which might be good)
                // these are for the new top100 content type
                'rank'              => 'field_hci100_rank.und.0.value',
            	'previousRank'      => 'field_hci100_previous_rank.und.0.value',
            	'founded'           => 'field_hci100_founded.und.0.value',
            	'companyType'       => 'field_hci100_company_type.und.0.value',
            	'employees'         => 'field_hci100_employees.und.0.value',
            	'revenueCurrent'    => 'field_hci100_revenue_current.und.0.value',
            	'revenuePrior1'     => 'field_hci100_revenue_prior1.und.0.value',
            	'revenuePriorYear1' => 'field_hci100_revenue_prior1_yyyy.und.0.value',
            	'revenuePrior2'     => 'field_hci100_revenue_prior2.und.0.value',
            	'revenuePriorYear2' => 'field_hci100_revenue_prior2_yyyy.und.0.value',
            	'companyExecutives' => 'field_hci100_company_executives.und.0.value',
            	'majorRevenue'      => 'field_hci100_major_revenue.und.0.value',
            	'productCategories' => 'field_hci100_product_categories.und.0.value',
	            'marketsServing'    => 'field_hci100_markets_serving.und.0.value',
            ]
        ]
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
        } else {
            $sourceField = $this->map['structure'][$baseName];
            //if (count($sourceField == 1)) $sourceField = current($sourceField);
        }
        return $sourceField;
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
    protected function queryNodes(array $types = [], $limit = 100, $skip = 0)
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

        // straight to legacy refs for resolution in base postimport segment
        if (!empty($taxonomy)) {
            $legacySource = sprintf('%s_taxonomy', $this->getKey());
            $node->legacy['refs']['taxonomy'][$legacySource] = $taxonomy;
        }
        
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
     * {@inheritdoc}
     */
    protected function convertFields(&$node)
    {
        // @jpdev - just use array the whole time?
        // due to storing entire old node in legacy.raw, need to increase the depth of encoding
        $nodeArray = json_decode(json_encode($node, 512), true);

        $node->type = str_replace('Website\\Content\\', '', $this->map['Content'][$node->type]);
        
        // method for each base4 element
        // _id
        $fieldName = $this->getFieldMapName('_id');
        if (!empty($fieldName)) {
            $node->_id = (int) $node->$fieldName;
        }

        // name
        $fieldName = $this->getFieldMapName('name');
        if (!empty($fieldName)) {
            $node->name = $node->$fieldName;
        }
        
        // status
        $fieldName = $this->getFieldMapName('status');
        if (!empty($fieldName)) {
            $node->status = (int) $node->$fieldName;
        }
        
        // createdBy
        $fieldName = $this->getFieldMapName('createdBy');
        if (!empty($fieldName)) {
            $legacySource = sprintf('%s_user', $this->getKey());
            $node->legacy['refs']['createdBy'][$legacySource] = $node->$fieldName;
        }
        
        // updatedBy
        $fieldName = $this->getFieldMapName('updatedBy');
        if (!empty($fieldName)) {
            $legacySource = sprintf('%s_user', $this->getKey());
            $node->legacy['refs']['updatedBy'][$legacySource] = $node->$fieldName;
        }
        
        // created
        $fieldName = $this->getFieldMapName('created');
        if (!empty($fieldName)) {
            $node->created = (int) $node->$fieldName;
        }
        
        // updated
        $fieldName = $this->getFieldMapName('updated');
        if (!empty($fieldName)) {
            $node->updated = (int) $node->$fieldName;
        }
        
        // published
        $fieldName = $this->getFieldMapName('published');
        if (!empty($fieldName)) {
            $node->published = (int) $node->$fieldName;
        }
        
        // redirects
        $node->mutations = [];
        $node->mutations['Website']['redirects'][] = drupal_get_path_alias(sprintf('node/%s', $node->_id));
        $node->mutations['Website']['redirects'][] = sprintf('node/%s', $node->_id);
        $fieldName = $this->getFieldMapName('mutations');
        if (!empty($fieldName) && !empty($node->$fieldName)) {
            $node->mutations['Website']['redirects'][] = $node->$fieldName;
        }
        
        // body
        $fieldName = $this->getFieldMapName('body');
        if (!empty($fieldName)) {
            $body = $this->resolveDotNotation($nodeArray, $fieldName);
            if (!empty($body)) {
                $node->body = $body;
            } else {
                // since body is a field in drupal and base, even if empty we have to set to empty (or will be left with drupal's language.[].value, etc format)
                $node->body = null;
            }
        }
        
        // teaser
        $fieldName = $this->getFieldMapName('teaser');
        if (!empty($fieldName)) {
            $teaser = $this->resolveDotNotation($nodeArray, $fieldName);
            if (!empty($teaser)) {
                $node->teaser = $teaser;
            }
        }
        
        // authors
        $fieldName = $this->getFieldMapName('authors');
        if (!empty($fieldName)) {
            $authors = $this->resolveDotNotation($nodeArray, $fieldName);
            if (!empty($authors)) {
                if (!is_array($authors)) $authors = (array) $authors;
                foreach ($authors AS $author) {
                    // @jpdev - they have name, title (David Raths, Contributing Editor) - should I attempt to parse, or leave verbatium? -- latter for now
                    $contact = [
                        'name'  => trim($author),    
                    ];
                    $this->importContact($contact);
                    $legacySource = sprintf('%s_contacts', $this->getKey());
                    $node->legacy['refs']['authors'][ $legacySource][] = trim($author);
                }
            }
        }
        
        // deck 
        // @jpdev - put in root, or in mutations.Magazine
        $fieldName = $this->getFieldMapName('deck');
        if (!empty($fieldName)) {
            $teaser = $this->resolveDotNotation($nodeArray, $fieldName);
            if (!empty($teaser)) {
                //$node->deck = $teaser;
                $node->mutations['Magazine']['deck']= $teaser;
            }
        }
        
        $fieldName = $this->getFieldMapName('images');
        if (!empty($fieldName)) {
            $images = $this->resolveDotNotation($nodeArray, $fieldName);
            if (!empty($images)) {
                foreach ($images AS $image) {
                    if (0 === (int) $image['fid']) continue;
                    
                    $fp = file_create_url($image['uri']);
                    $node->legacy['refs']['images']['common'][] = $fp;
                    
                    // add image also
                    $caption = null;
                    if (isset($node->field_image_caption)) {
                        $val = $this->getFieldValue($node->field_image_caption, $node, null);
                        if (null !== $val) {
                            $caption = $val['value'];
                        }
                    }
                    if (isset($node->field_image_text)) {
                        $val = $this->getFieldValue($node->field_image_text, $node, null);
                        if (null !== $val) {
                            $caption = $val['value'];
                        }
                    }
                    $this->createImage($image, $caption);

                    if (!isset($node->primaryImage)) {
                        //$node->legacy['refs']['primaryImage']['common'] = (String) $image['fid'];
                        $node->legacy['refs']['primaryImage']['common'] = (String) $fp;
                    }
                }
            }
        }
         
        // added for hci100
        $fieldName = $this->getFieldMapName('phone');
        if (!empty($fieldName)) {
            $phone = $this->resolveDotNotation($nodeArray, $fieldName);
            if (!empty($phone)) {
                $node->phone = $phone;
            }
        }

        $fieldName = $this->getFieldMapName('city');
        if (!empty($fieldName)) {
            // try to parse City, State
            $city = $this->resolveDotNotation($nodeArray, $fieldName);
            if (!empty($city)) {
                $cityParts = explode(",", $city);
                if (count($cityParts) == 2) {
                    $node->city = trim($cityParts[0]);
                    $node->state = trim($cityParts[1]);
                } else {
                    $node->city = trim($city);
                }
            }
        }
        
        $fieldName = $this->getFieldMapName('website');
        if (!empty($fieldName)) {
            $website = $this->resolveDotNotation($nodeArray, $fieldName);
            if (!empty($website)) {
                $node->website = $website;
            }
        }
        
        // socialLinks returning array
        $fieldNameArray = $this->getFieldMapName('socialLinks');
        if (!empty($fieldName)) {
            $socialLinks = array();
            foreach ($fieldNameArray AS $fieldName) {
                $socialData = $this->resolveDotNotation($nodeArray, $fieldName);
                
                if (empty($socialData)) continue;
                
                if (strpos($fieldName, 'twitter') !== false) {
                    $data = [
                        'provider'  => 'twitter',
                        'label'     => 'Twitter',
                        'url'       => $socialData
                    ];
                    $socialLinks[] = $data;
                }
                if (strpos($fieldName, 'linkedin') !== false) {
                    $data = [
                        'provider'  => 'linkedin',
                        'label'     => 'LinkedIn',
                        'url'       => $socialData
                    ];
                    $socialLinks[] = $data;
                }
                if (strpos($fieldName, 'facebook') !== false) {
                    $data = [
                        'provider'  => 'facebook',
                        'label'     => 'Facebook',
                        'url'       => $socialData
                    ];
                    $socialLinks[] = $data;
                }
            }
            if (!empty($socialLinks)) {
                $node->socialLinks = $socialLinks;
            }
        }
        
        $fieldName = $this->getFieldMapName('rank');
        if (!empty($fieldName)) {
            $rank = $this->resolveDotNotation($nodeArray, $fieldName);
            if (!empty($rank)) {
                $node->rank = $rank;
            }
        }
        
        $fieldName = $this->getFieldMapName('previousRank');
        if (!empty($fieldName)) {
            $previousRank = $this->resolveDotNotation($nodeArray, $fieldName);
            if (!empty($previousRank)) {
                $node->previousRank = $previousRank;
            }
        }
        
        $fieldName = $this->getFieldMapName('founded');
        if (!empty($fieldName)) {
            $founded = $this->resolveDotNotation($nodeArray, $fieldName);
            if (!empty($founded)) {
                $node->founded = $founded;
            }
        }
        
        $fieldName = $this->getFieldMapName('companyType');
        if (!empty($fieldName)) {
            $companyType = $this->resolveDotNotation($nodeArray, $fieldName);
            if (!empty($companyType)) {
                $node->companyType = $companyType;
            }
        }
        
        $fieldName = $this->getFieldMapName('employees');
        if (!empty($fieldName)) {
            $employees = $this->resolveDotNotation($nodeArray, $fieldName);
            if (!empty($employees)) {
                $node->employees = $employees;
            }
        }
        
        $fieldName = $this->getFieldMapName('revenueCurrent');
        if (!empty($fieldName)) {
            $revenueCurrent = $this->resolveDotNotation($nodeArray, $fieldName);
            if (!empty($revenueCurrent)) {
                $node->revenueCurrent = $revenueCurrent;
            }
        }
        
        
        $fieldName = $this->getFieldMapName('revenuePrior1');
        if (!empty($fieldName)) {
            $revenuePrior1 = $this->resolveDotNotation($nodeArray, $fieldName);
            if (!empty($revenuePrior1)) {
                $node->revenuePrior1 = $revenuePrior1;
            }
        }
        
        $fieldName = $this->getFieldMapName('revenuePriorYear1');
        if (!empty($fieldName)) {
            $revenuePriorYear1 = $this->resolveDotNotation($nodeArray, $fieldName);
            if (!empty($revenuePriorYear1)) {
                $node->revenuePriorYear1 = $revenuePriorYear1;
            }
        }
        
        $fieldName = $this->getFieldMapName('revenuePrior2');
        if (!empty($fieldName)) {
            $revenuePrior2 = $this->resolveDotNotation($nodeArray, $fieldName);
            if (!empty($revenuePrior2)) {
                $node->revenuePrior2 = $revenuePrior2;
            }
        }
        
        $fieldName = $this->getFieldMapName('revenuePriorYear2');
        if (!empty($fieldName)) {
            $revenuePriorYear2 = $this->resolveDotNotation($nodeArray, $fieldName);
            if (!empty($revenuePriorYear2)) {
                $node->revenuePriorYear2 = $revenuePriorYear2;
            }
        }
        
        $fieldName = $this->getFieldMapName('companyExecutives');
        if (!empty($fieldName)) {
            $companyExecutives = $this->resolveDotNotation($nodeArray, $fieldName);
            if (!empty($companyExecutives)) {
                $node->companyExecutives = $companyExecutives;
            }
        }
        
        $fieldName = $this->getFieldMapName('majorRevenue');
        if (!empty($fieldName)) {
            $majorRevenue = $this->resolveDotNotation($nodeArray, $fieldName);
            if (!empty($majorRevenue)) {
                $node->majorRevenue = $majorRevenue;
            }
        }
        
        $fieldName = $this->getFieldMapName('productCategories');
        if (!empty($fieldName)) {
            $productCategories = $this->resolveDotNotation($nodeArray, $fieldName);
            if (!empty($productCategories)) {
                $node->productCategories = $productCategories;
            }
        }
        
        $fieldName = $this->getFieldMapName('marketsServing');
        if (!empty($fieldName)) {
            $marketsServing = $this->resolveDotNotation($nodeArray, $fieldName);
            if (!empty($marketsServing)) {
                $node->marketsServing = $marketsServing;
            }
        }

        $oldFields = ['field_image_caption']; // caption used with field_image
        $newFields = ['_id', 'type', 'legacy', ];  
        foreach ($this->map['structure'] AS $baseFieldName => $drupalFieldNames) {
            $newFields[] = $baseFieldName;
            if (!is_array($drupalFieldNames)) $drupalFieldNames = (array)$drupalFieldNames;
            
            // dot notation drupal fields will remove fromt the root element (body.value will remove body and all children unless body is in the newField list as well)
            foreach ($drupalFieldNames AS &$drupalFieldName) {
                $drupalFieldName = explode('.', $drupalFieldName)[0];
            }
            $oldFields = array_unique(array_merge($oldFields, $drupalFieldNames));
        }
        $removeFields = array_diff($oldFields, $newFields);
        foreach ($removeFields AS $removeField) {
            unset($node->$removeField);
        }
        $nodeFields = array_keys($nodeArray);
        
        // don't move fields that are in the newField (B4) list
        $moveFields = array_diff($nodeFields, $newFields);
        
        // don't move fields that were sources from the oldField (D7) list
        $moveFields = array_diff($moveFields, $oldFields);

        foreach ($moveFields AS $moveField) {
            $unsupportedFields[$moveField] = $node->$moveField;
            unset($node->$moveField);
        }
        $node->legacy['unsupportedFields'] = $unsupportedFields;

    }

    

    /**
     * {@inheritdoc}
     * 
     * Creates an image to create as Asset in base4
     * 
     */
    protected function createImage(array $img, $caption = null)
    {
        if ((int) $img['fid'] === 0) {
            return;
        }

        $filePath = file_create_url($img['uri']);

        $collection = $this->database->selectCollection('Image');
        $kv = [
            '_id'       => (int) $img['fid'],
            'type'      => 'Image',
            'name'      => $img['filename'],
            'fileName'  => $img['filename'],
            'source'    => [
                'location'  => $filePath,
                'name'      => $img['filename'],
            ],
            'legacy'    => [
                'id'      => $filePath,
                'source'    => sprintf('common'),
                'created'   => date('c', $img['timestamp']),
            ]
        ];
        if (null !== $caption) {
            $kv['caption'] = $caption;
        }

        try {
            $collection->insert($kv);
        } catch (\Exception $e) {
            var_dump('some error - dupe fid?');  
            var_dump($kv);
        }
    }
    
    /**
     * {@inheritdoc}
     * 
     * Creates an image to create as Asset in base4
     * 
     */
    protected function importTaxonomy($vocab)
    {
        $collection = $this->database->selectCollection('Taxonomy');
        $terms = taxonomy_get_tree($vocab->vid);
        $type = str_replace('Taxonomy\\', '', $this->map['Taxonomy'][$vocab->name]);
        $formatted = [];
        foreach ($terms as $term) {
            if ((int) $term->tid === 0) {
                continue;
            }
            $alias = taxonomy_term_uri($term);
            $alias = $alias['path'];
            if (false !== $path = drupal_lookup_path('alias', $alias)) {
                $alias = $path;
            }
            
            $formatted[] = [
                '_id'           => (int) $term->tid,
                //'name'          => $term->name,
                'name'          => sprintf('%s -- %s', $vocab->name, $term->name),
                'description'   => $term->description,
                'type'          => $type,
                'alias'         => $alias,
                'legacy'        => [
                    'id'            => (String) $term->tid,
                    'source'        => sprintf('%s_taxonomy', $this->getKey())
                ]
            ];
        }

        if (!empty($formatted)) {
            $this->writeln(sprintf('Vocabulary: Inserting %s %s terms.', count($formatted), $type));
            $collection->batchInsert($formatted);
        }
    }
    
    /**
     * {@inheritdoc}
     * 
     * Creates an image to create as Asset in base4
     * 
     */
    protected function importContact($contact) {
        $collection = $this->database->selectCollection('Content');
        $formatted[] = [
            'name'          => $contact['name'],
            'type'          => 'Contact',
            'legacy'        => [
                'id'            => $contact['name'],
                'source'        => sprintf('%s_contacts', $this->getKey())
            ]
        ];
        if (!empty($formatted)) {
            $collection->batchInsert($formatted);
        }
    }
    
    /**
     * {@inheritdoc}
     * 
     * Create Issue elements to migrate to base4 Magazine/Issue entries
     * 
     */
    protected function importMagazineIssueNodes()
    {
        if (!isset($this->map['Issue']) || empty($this->map['Issue'])) {
            $this->writeln(sprintf('You must set the issue map for %s:', $this->key), false, true);
            $types = $this->getTypes();
            $this->writeln(sprintf('Valid types: %s', implode(', ', $types)), true, true);
        }

        $collection = $this->database->selectCollection('Issue');
        $types = array_keys($this->map['Issue']);

        $count = $total = (int) $this->countNodes($types);

        $this->writeln(sprintf('Nodes: Importing %s Magazine Issues.', $count));

        $nodes = $this->queryNodes($types, 0, 0);
        $tz = new DateTimeZone('America/Chicago');

        $formatted = [];
        foreach ($nodes as $node) {

            $kv = [
                '_id'               => (int) $node->nid,
                'name'              => $node->title,
                'created'           => $node->created,
                'updated'           => $node->changed,
                'status'            => (int) $node->status,
            ];
            
            if (!empty($node->body)) $kv['description'] = $node->body;
            
            $kv['legacy']['id'] = (string) $node->nid;
            $kv['legacy']['source'] = sprintf('%s_issue', $this->getKey());
            
            //$nodeArray = json_decode(json_encode($node, 512), true);
            $kv['legacy']['raw'] = $node;
            
            // mailDate not defined in drupal, titles are often close enough to calculate, so try, fallback to Jan 2000 if all fails
            $mailDate = strtotime($node->title);
            if ($mailDate === false) {
                // mailDate was not cleanly formatted, perform usual adjustments to try to determine mailDate
                
                // trim it up
                $title = trim($node->title);
                
                // First/Second/Third/Fouth Quarter
                $title = str_replace('First Quarter', 'January', $title);
                $title = str_replace('Second Quarter', 'April', $title);
                $title = str_replace('Third Quarter', 'July', $title);
                $title = str_replace('Fourth Quarter', 'October', $title);
                
                // strip 'Digital Suppliment'
                $title = str_replace(' Digital Supplement', '', $title);
                
                // April/May 2017
                if (false !== strpos($title, '/')) {
                    $slashParts = explode("/", $title);
                    $spaceParts = explode(" ", $slashParts[1]);
                    $title = sprintf("%s %s", $slashParts[0], $spaceParts[1]);
                }
               
                // try to do mailDate again 
                $mailDate = strtotime($title);
                
                // if still bad, leave blank if set to ignore, otherwise use Jan 2000
                if ($mailDate === false) {
                    //$ignore = ['Online', 'HRCM 2007'];
                    if (!in_array($title, $ignore)) {
                        if ($title == 'HRCM 2007') {
                            $mailDate = strtotime('January 2007');
                        } else {
                            $mailDate = strtotime('January 2000');
                        }
                    }
                }
            }
            $mailDate = new DateTime(date('c', $mailDate), $tz);
            
            if (false !== $mailDate) $kv['mailDate'] = $mailDate->format('c');
   
            // coverImage
            if (!empty($node->field_image)) {
                $imageData = $node->field_image['und'][0];
                
                // create Image for later reference resolution
                $this->createImage($imageData);
                
                // set ref on issue as well
                $imageUrl = file_create_url($imageData['uri']);
                $kv['legacy']['refs']['coverImage']['common'] = $imageUrl;
            }
            if (false !== $mailDate) $kv['legacy']['shortName'] = strtolower($mailDate->format('My'));
            
            // @jpdev - Not sure if we can count on this always existing, use this or ScheduleMagaizne drush collection to do magaizne scheduling
            // atm base4 set to use both legacy.schedule.print on issue AND legacy.schedule.print.issue on articles to schedule
            if (!empty($node->field_noderef_article)) {
                $legacySource = sprintf('%s_nid', $this->getKey());
                foreach ($node->field_noderef_article['und'] AS $scheduleNid) {
                     $kv['legacy']['schedule']['print'][$legacySource][] = $scheduleNid['nid'];
                }
            }

            if (isset($node->field_nxtbook_link)) {
                foreach ($node->field_nxtbook_link as $link) {
                    if (array_key_exists('value', $link) && null === $link['value']) {
                        continue;
                    }
                    $kv['digitalEditionUrl'] = rtrim($link['value']);
                }
            }
            
            if (isset($node->field_issue_link)) {
                foreach ($node->field_issue_link as $link) {
                    if (array_key_exists('value', $link) && null === $link['value']) {
                        continue;
                    }
                    $kv['digitalEditionUrl'] = rtrim($link['value']);
                }
            }

            $formatted[] = $kv;
        }

        if (!empty($formatted)) {
            $this->writeln(sprintf('Nodes: Inserting %s Magazine Issues.', count($formatted)));
            $collection->batchInsert($formatted);
        }
        
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
