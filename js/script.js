(($, Drupal) => {
  Drupal.behaviors.validate = {
    attach() {
      $('.month').change(function () {

        if (parseFloat(this.value) < 0) {
          $('#' + this.id).val(0);
        }
        $('#' + this.id).val(parseFloat(this.value).toFixed(2));
        /*
         * active_cell_id - id зміненної комірки
         */
        let active_cell_id = this.id.split('-');

        /*
         * year_cell - id комірки року
         */
        let year_cell = active_cell_id[0] + '-' + active_cell_id[1] + '-17';
        /*
         * template - шаблон для поточних комірок table-row-
         */
        let template = active_cell_id[0] + '-' + active_cell_id[1] + '-';
        /*
         * active_cell_n - номер зміненої комірки
         */
        let active_cell_n = parseInt(active_cell_id[2], 10);
        /*
         * value_cells - значення місяців кварталу
         */
        let value_cells = [];

        if ([1, 5, 9, 13].includes(active_cell_n)) {
          value_cells[0] = getNumberFloat(template + active_cell_n++);
          value_cells[1] = getNumberFloat(template + active_cell_n++);
        }
        else if ([2, 6, 10, 14].includes(active_cell_n)) {
          value_cells[0] = getNumberFloat(template + (active_cell_n - 1));
          value_cells[1] = getNumberFloat(template + active_cell_n++);
        }
        else {
          value_cells[0] = getNumberFloat(template + (active_cell_n - 1));
          value_cells[1] = getNumberFloat(template + (active_cell_n - 2));
        }
        value_cells[2] = getNumberFloat(template + active_cell_n++);

        /*
         * quarter - результат обчислення кварталу
         */
        let quarter = value_cells[0] + value_cells[1] + value_cells[2];
        /*
         * sum_year - сума за рік
         */
        let sum_year;
        if (quarter) {
          quarter = ((quarter + 1) / 3).toFixed(2);
        }
        /*
         * $id_quarter id обрахованого кварталу
         */
        let id_quarter = template + active_cell_n;
        /*
         * value_cells - значення кварталів
         */
        value_cells = [];
        value_cells['4'] = getNumberFloat(template + '4');
        value_cells['8'] = getNumberFloat(template + '8');
        value_cells['12'] = getNumberFloat(template + '12');
        value_cells['16'] = getNumberFloat(template + '16');
        value_cells[active_cell_n] = parseFloat(quarter);

        sum_year = value_cells['4'] + value_cells['8'] + value_cells['12'] + value_cells['16'];

        if (sum_year) {
          sum_year = ((sum_year + 1) / 4).toFixed(2);
        }

        $('#' + id_quarter).val(quarter);
        $('#' + year_cell).val(sum_year);
      });

      function getNumberFloat(id) {
        let number = parseFloat($('#' + id).val());
        return isNaN(number) ? 0 : number;
      }
    },
  };
})(jQuery, Drupal);
