<?php

namespace Drupal\validate\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class Form
 *
 * @package Drupal\validate\Form
 */
class Form extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'validate_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $num_of_rows = $form_state->get('num_of_rows');
    $cell_value = $form_state->getUserInput();
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

    // Adding tables
    for ($j = 1; $j <= count($num_of_rows); $j++) {
      $table = 'table' . $j;

      // Adding table captions
      $form[$table] = [
        '#type' => 'table',
        '#header' => ['Year'],
      ];
      for ($i = 0; $i < count($name); $i++) {
        array_push($form[$table]['#header'], $name[$i]);
      }

      array_push($form[$table]['#header'], 'YTD');

      // Adding rows
      $time_value = \Drupal::time()->getCurrentTime();
      $year = \Drupal::service('date.formatter')
        ->format($time_value, 'custom', 'Y');

      for ($i = 1; $i <= $num_of_rows[$table]; $i++) {

        // Adding cell Year
        $form[$table][$i]['Year'] = [
          '#type' => 'html_tag',
          '#tag' => 'b',
          '#value' => $year - $num_of_rows[$table] + $i,
        ];

        // Adding cells month and quarter
        for ($k = 1; $k <= count($name); $k++) {

          // Format name_cell: 'table-row-cell'
          $name_cell = $j . '-' . ($num_of_rows[$table] - $i + 1) . '-' . $k;

          $form[$table][$i][$name[$k - 1]] = [
            '#type' => 'number',
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
            $name_cell .= '-month';
            $form[$table][$i][$name[$k - 1]]['#attributes'] = [
              'class' => ['month'],
            ];
          }
          if (isset($cell_value[$name_cell])) {
            $value_cell = $cell_value[$name_cell];
          }
          else {
            $value_cell = '';
          }

          $form[$table][$i][$name[$k - 1]]['#value'] = $value_cell;
          $form[$table][$i][$name[$k - 1]]['#name'] = $name_cell;
          $form[$table][$i][$name[$k - 1]]['#id'] = $name_cell;
        }

        // Adding cell YTD
        $name_cell = $j . '-' . ($num_of_rows[$table] - $i + 1) . '-17';
        if (isset($cell_value[$name_cell])) {
          $value_cell = $cell_value[$name_cell];
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

      // Adding row add button
      $form['add_row_' . $table] = [
        '#type' => 'submit',
        '#value' => $this->t('Add row'),
        '#name' => $table,
        '#submit' => ['::addRowCallback'],
        '#attributes' => ['class' => ['add_row']],
      ];

    }
    $form['#attached']['library'][] = 'validate/validate_css_js';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    if (isset($form_state->getUserInput()['op']) && $form_state->getUserInput()['op'] === "Submit") {
      $array_rows = [];
      $index = -1;
      foreach ($form_state->getUserInput() as $key => $value) {
        if (preg_match('/\d+-\d+-\d+-month/', $key)) {
          $id = explode('-', $key);
          if ($id[0] - 1 > $index) {
            $index++;
            $array_rows[$index] = '';
          }
          if ($value !== '') {
            $array_rows[$index] .= '1';
          }
          else {
            $array_rows[$index] .= ' ';
          }
          if (strpos(trim($array_rows[$index]), ' ')) {
            $form_state->set('valid', FALSE);
            return;
          }
        }
      }

      // Search for the longest string
      $max_line = '';
      for ($i = 0; $i < count($array_rows); $i++) {
        if (trim($array_rows[$i]) === '') {
          continue;
        }
        elseif (strlen($array_rows[$i]) > strlen($max_line)) {
          $max_line = $array_rows[$i];
        }
      }

      // Comparison of tables
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
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    if ($form_state->get('valid')) {
      \Drupal::messenger()->addStatus('Valid!');
    }
    else {
      \Drupal::messenger()->addError(t('Invalid!'));
    }
    $form_state->setRebuild();
  }

  /**
   * Function adding table
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function addTableCallback(array &$form, FormStateInterface $form_state) {
    $this->addCallback(TRUE, $form_state);
  }

  /**
   * Function adding row
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function addRowCallback(array &$form, FormStateInterface $form_state) {
    $this->addCallback(FALSE, $form_state);
  }

  /**
   * Function adding table or row in form
   *
   * @param bool $add
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  private function addCallback(bool $add, FormStateInterface $form_state) {
    $num_of_rows = $form_state->get('num_of_rows');
    if ($add) {
      $num_of_rows['table' . (count($num_of_rows) + 1)] = 1;
    }
    else {
      $table = array_values(preg_grep("/table.*/", array_keys($form_state->getUserInput())))[0];
      $num_of_rows[$table]++;
    }
    $form_state->set('num_of_rows', $num_of_rows);
    $form_state->setRebuild();
  }

}
