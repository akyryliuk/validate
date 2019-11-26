<?php

namespace Drupal\validate\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger;

/**
 * Class Form
 *
 * @package Drupal\validate\Form
 */
class Form extends FormBase {

  /**
   * @return string
   */
  public function getFormId() {
    return 'validate_form';
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * Function adding table
   */
  public function addTableCallback(array &$form, FormStateInterface $form_state) {
    $num_of_rows = $form_state->get('num_of_rows');
    $num_of_rows['table' . (count($num_of_rows) + 1)] = 1;
    $form_state->set('num_of_rows', $num_of_rows);
    $form_state->setRebuild();
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * Function adding row
   */
  public function addRowCallback(array &$form, FormStateInterface $form_state) {
    $num_of_rows = $form_state->get('num_of_rows');
    $table = array_values(preg_grep("/table.*/", array_keys($_POST)))[0];
    $num_of_rows[$table]++;
    $form_state->set('num_of_rows', $num_of_rows);
    $form_state->setRebuild();
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $num_of_rows = $form_state->get('num_of_rows');

    if (empty($num_of_rows)) {
      $num_of_rows['table1'] = 1;
      $form_state->set('num_of_rows', $num_of_rows);
    }

    $form['actions']['add_table'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add table'),
      '#submit' => ['::addTableCallback'],
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    $name = [
      'Jan',
      'Feb',
      'Mar',
      'Q1',
      'May',
      'Apr',
      'Jun',
      'Q2',
      'Jul',
      'Aug',
      'Sep',
      'Q3',
      'Oct',
      'Nov',
      'Dec',
      'Q4',
    ];
    /*
     * Adding tables
     */
    for ($j = 1; $j <= count($num_of_rows); $j++) {
      $table = 'table' . $j;
      /*
       * Adding table captions
       */
      $form[$table] = [
        '#type' => 'table',
        '#header' => ['Year'],
      ];
      for ($i = 0; $i < count($name); $i++) {
        array_push($form[$table]['#header'], $name[$i]);
      }

      array_push($form[$table]['#header'], 'YTD');
      /*
       *  Adding rows
       */
      $year = date("Y");

      for ($i = 1; $i <= $num_of_rows[$table]; $i++) {

        /*
         * Adding cell Year
         */
        $form[$table][$i]['Year'] = [
          '#type' => 'html_tag',
          '#tag' => 'b',
          '#value' => $year - $num_of_rows[$table] + $i,
        ];

        /*
         * Adding cells month and quarter
         */
        for ($k = 1; $k <= count($name); $k++) {
          /*
           * Format name_cell: 'table-row-cell'
           */
          $name_cell = $j . '-' . ($num_of_rows[$table] - $i + 1) . '-' . $k;
          if (isset($_POST[$name_cell])) {
            $value_cell = $_POST[$name_cell];
          }
          else {
            $value_cell = '';
          }

          $form[$table][$i][$name[$k - 1]] = [
            '#type' => 'number',
            '#name' => $name_cell,
            '#id' => $name_cell,
            '#value' => $value_cell,
            '#min' => 0,
            '#step' => 0.01,
          ];

          if ($k % 4 == 0) {
            $form[$table][$i][$name[$k - 1]]['#attributes'] = [
              'class' => ['quarter'],
              'readonly' => 'readonly',
            ];
          }
          else {
            $form[$table][$i][$name[$k - 1]]['#attributes'] = [
              'class' => ['month'],
            ];
          }
        }

        /*
         * Adding cell YTD
         */
        $name_cell = $j . '-' . ($num_of_rows[$table] - $i + 1) . '-17';
        if (isset($_POST[$name_cell])) {
          $value_cell = $_POST[$name_cell];
        }
        else {
          $value_cell = '';
        }

        $form[$table][$i]['YTD'] = [
          '#type' => 'number',
          '#value' => $value_cell,
          '#name' => $name_cell,
          '#id' => $name_cell,
          '#step' => 0.01,
          '#attributes' => ['class' => ['quarter'], 'readonly' => 'readonly'],
        ];

      }

      /*
       * Adding row add button
       */
      $form['add_row_' . $table] = [
        '#type' => 'submit',
        '#value' => $this->t('Add row'),
        '#name' => $table,
        '#submit' => ['::addRowCallback'],
        '#attributes' => ['class' => ['add_row']],
      ];

    }
    return $form;
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    if ($_POST['op'] == "Submit") {
      $array_rows = [];
      $index = 0;
      foreach ($_POST as $key => $value) {
        if ((int) $key) {
          $id = explode('-', $key);
          if ($id[0] - 1 > $index) {
            $index = $id[0] - 1;
            $array_rows[$index] = '';
          }
          if (!in_array($id[2], [4, 8, 12, 16, 17])) {
            if ($value !== '') {
              $array_rows[$index] .= '1';
            }
            else {
              $array_rows[$index] .= ' ';
            }
          }
          if (strpos(trim($array_rows[$index]), ' ')) {
            $form_state->set('valid', FALSE);
            return;
          }
        }
      }
      /*
       * Search for the longest string
       */
      $max_line = '';
      for ($i = 0; $i < count($array_rows); $i++) {
        if (trim($array_rows[$i]) === '') {
          continue;
        }
        elseif (strlen($array_rows[$i]) > strlen($max_line)) {
          $max_line = $array_rows[$i];
        }
      }
      /*
       * Comparison of tables
       */
      for ($i = 0; $i < count($array_rows); $i++) {
        if (trim($array_rows[$i]) === '') {
          continue;
        }
        $state = substr_compare($max_line, $array_rows[$i], strlen($max_line) - strlen($array_rows[$i]));
        if ($state) {
          $form_state->set('valid', FALSE);
          return;
        }
      }
      $form_state->set('valid', TRUE);
    }
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    if ($form_state->get('valid')) {
      \Drupal::messenger()->addStatus('Valid!');
    }
    else {
      \Drupal::messenger()->addError(t('Not valid!'));
    }
    $form_state->setRebuild();
  }

}
