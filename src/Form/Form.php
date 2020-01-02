<?php

namespace Drupal\validate\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CssCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Block\BlockBase;
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
    $form['#prefix'] = '<div id="my-id">';
    $form['#suffix'] = '</div>';

    $form['actions']['add_table'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add table'),
      '#submit' => ['::addTableCallback'],
    ];

    $form['system_messages'] = [
      '#markup' => '<div id="form-messages-validate"></div>',
      '#weight' => -100,
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#ajax' => [
        'callback' => '::validateAndSubmitForm',
        'progress' => ['type' => 'none'],
      ],
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

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function validateAndSubmitForm(array &$form, FormStateInterface $form_state) {
    $index = 0;
    $array_rows = ['', ''];
    $valid = TRUE;
    foreach ($form_state->getUserInput() as $key => $value) {
      if (preg_match('/\d+-\d+-\d+-month/', $key)) {
        $id = explode('-', $key);

        $value !== '' ? $array_rows[$index] .= '1' : $array_rows[$index] .= ' ';

        if (strpos(trim($array_rows[$index]), ' ')) {
          $valid = FALSE;
          break;
        }
        //if end of table (last row and last cell)
        if ($id[1] == 1 && $id[2] == 15) {
          if (trim($array_rows[$index]) === '') {
            $array_rows[$index] = '';
            continue;
          }
          elseif (!$index) {
            $index = 1;
          }
          // Comparison of tables if there are two strings
          if (!empty($array_rows[0]) && !empty($array_rows[1])) {
            if (strlen($array_rows[0]) > strlen($array_rows[1])) {
              $state = substr_compare($array_rows[0], $array_rows[1], strlen($array_rows[0]) - strlen($array_rows[1]));
            }
            else {
              $state = substr_compare($array_rows[1], $array_rows[0], strlen($array_rows[1]) - strlen($array_rows[0]));
              array_shift($array_rows);
            }
            if ($state) {
              $valid = FALSE;
              break;
            }
            $array_rows[1] = '';
          }
        }
      }
    }

    if ($valid) {
      $messages = 'Valid!';
      $css = [
        'display' => 'block',
        'color' => '#325e1c',
        'border-color' => '#c9e1bd',
        'background-color' => '#f3faef',
        'box-shadow' => '-8px 0 0 #77b259',
      ];
    }
    else {
      $messages = 'Invalid!';
      $css = [
        'display' => 'block',
        'color' => '#a51b00',
        'border-color' => '#f9c9bf',
        'background-color' => '#fcf4f2',
        'box-shadow' => '-8px 0 0 #e62600',
      ];
    }
    $selector = '#form-messages-validate';
    $ajax_response = new AjaxResponse();
    $ajax_response->addCommand(new CssCommand($selector, $css));
    $ajax_response->addCommand(new HtmlCommand($selector, $messages));
    return $ajax_response;
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
