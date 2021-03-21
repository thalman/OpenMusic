<?php

namespace Drupal\openmusic\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\openmusic\Entity\OpenMusicScore;

/*
Js uplaod
<a href="#">Just click me.</a>
<script type="text/javascript">
    $("a").click(function() {
        // creating input on-the-fly
        var input = $(document.createElement("input"));
        input.attr("type", "file");
        // add onchange handler if you wish to get the file :)
        input.trigger("click"); // opening dialog
        return false; // avoiding navigation
    });
</script>

*/

/**
 * Implements an example form.
 */
class UploadForm extends FormBase {

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'openmusic_upload_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        $form['score_file'] = [
            '#type' => 'managed_file',
            '#title' => $this->t('Score file'),
            '#upload_location' => 'private://openmusic/tmp',
            '#multiple' => FALSE,
            '#upload_validators' => [
                'file_validate_extensions' => ['mscz'],
            ],
        ];
        $form['score_id'] = [
            '#type' => 'hidden',
            '#value' => '0',
        ];
        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#id' => "scoreuploadbutton",
            '#value' => $this->t('Upload'),
            '#button_type' => 'primary',
        ];
        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {
        // if (strlen($form_state->getValue('phone_number')) < 3) {
        //   $form_state->setErrorByName('phone_number', $this->t('The phone number is too short. Please enter a full phone number.'));
        // }
        //$form_state->setErrorByName('file', $this->t('Not implemented yet'));
        //if ($form_state->getValue('score_file') == NULL) {
        //    $form_state->setErrorByName('score_file', $this->t('File is mandatory.'));
        //}
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        //$this->messenger()->addStatus($this->t('Your phone number is @number', ['@number' => $form_state->getValue('phone_number')]));
        //$storage = \Drupal::service('file_system')->realpath("private://");

        $fid = $form_state->getValue(['score_file', 0]);
        if (!$form_state->getErrors() && !empty($fid)) {
            $score = OpenMusicScore::create();
            $score->scoreCreate($fid);
        }
        // error_log("var:" . $files);
        //$this->messenger()->addStatus($this->t('File upload submitted'));
    }
}
