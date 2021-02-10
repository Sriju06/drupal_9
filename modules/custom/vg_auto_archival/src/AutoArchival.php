<?php

namespace Drupal\vg_auto_archival;

/**
* Class AutoArchival.
*/
class AutoArchival {

    /**
     * Set content state to Archived.
    */
    public function setStateToArchive ($nids, $logger, &$context) {
        // Use the context to store the information needed to track progression.
        if (empty($context['sandbox'])) {
            // The count of entities visited so far.
            $context['sandbox']['progress'] = 0;
            $context['sandbox']['current_id'] = 0;
            // Total entities that must be visited.
            $context['sandbox']['max'] = count($nids);
            // A place to store messages during the run.
        }
        $message = 'Archiving Nodes...';
        
        $limit = 20;
        $nids = array_slice($nids, $context['sandbox']['progress'], $limit);

        foreach ($nids as $key => $nid) {
            $node = \Drupal::service('entity_type.manager')->getStorage('node')->load($nid);
            $node->set('moderation_state', 'archived');
            $node->save();
            $logger->notice('The node id: ' . $nid . ' has been archived');
            $context['sandbox']['progress']++;
            $context['sandbox']['current_id'] = $nid;
        }
        if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
            $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
        }
    }

    /**
     * Function is called when batch process ends.
     */
    public static function setStateToArchiveCallback($success, $nids, $operations) {
        // The 'success' parameter means no fatal PHP errors were detected. All
        // other error management should be handled using 'results'.
        if ($success) {
            $message = \Drupal::translation()->formatPlural(
                count($nids),
                'One post processed.', '@count posts processed.'
            );
        }
        else {
            $message = t('Finished with an error.');
        }
    }
}