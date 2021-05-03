<?php

namespace Calendar;

class Calendar {
    private $target_day_timestamp;
    private $meta_data;
    private $header_html = ["<calendar-header>Sunday</calendar-header>",
                            "<calendar-header>Monday</calendar-header>",
                            "<calendar-header>Tuesday</calendar-header>",
                            "<calendar-header>Wednesday</calendar-header>",
                            "<calendar-header>Thursday</calendar-header>",
                            "<calendar-header>Friday</calendar-header>",
                            "<calendar-header>Saturday</calendar-header>"];

    /**
     * Constructs the Calendar and stores the input date uniformly as a unix timestamp.
     * You can specify the meta data for the styling or use the defaults.
     * 
     * INPUT: $date_stamp as UNIX TIMESTAMP | "YYYY-MM-DD".
     * INPUT: $meta_data as "day_id"=>"" | "data_date"=>"" | "day_class"=>"" |
     * "week_class"=>"" | "data_week_of_year"=>"" | "month_class"=>"".
     * 
     * TODO: Input validation.
     */
    public function __construct($date_stamp, $meta_data = []) {
        $this->target_day_timestamp = $date_stamp;
        // $this->target_day_timestamp = strtotime($date_stamp);
        // if($this->target_day_timestamp === false) {
        //     $this->target_day_timestamp = time();
        // }

        $meta_data = array_merge(["day_id"=>"", "data_date"=>"", "day_class"=>"",
            "week_class"=>"", "data_week_of_year"=>"", "month_class"=>""], $meta_data);
        $this->meta_data = $meta_data;
    }

    /**
     * OUTPUT: The date as an individual day cell with proper layout and styling.
     */
    public function draw_day() {
        return "<calendar-table class='calendar--current-month'>
                    <calendar-week class='calendar--header' data-week-of-year='header'>" .
                        $this->header_html[date("w", $this->target_day_timestamp)] .
                    "</calendar-week>
                    <calendar-week data-week-of-year='13'>" .
                        $this->make_day_html($this->target_day_timestamp) .
                    "</calendar-week>
                </calendar-table>";
    }

    /**
     * OUTPUT: The date as a group of cells with proper layout and styling.
     */
    public function draw_week() {
        return "<calendar-table class='calendar--current-month'>
                    <calendar-week class='calendar--header' data-week-of-year='header'>" .
                        implode("", $this->header_html) .
                    "</calendar-week>" .
                    $this->make_week_html($this->target_day_timestamp) .
                "</calendar-table>";
    }

    /**
     * OUTPUT: The date as a group of cells with proper layout and styling.
     */
    public function draw_month() {
        return "<calendar-table class='calendar--current-month'>
                    <calendar-week class='calendar--header' data-week-of-year='header'>" .
                        implode("", $this->header_html) .
                    "</calendar-week>" .
                    $this->make_month_html($this->target_day_timestamp) .
                "</calendar-table>";
    }

    /**
     * OUTPUT: Example HTRML [DELETE THIS WHEN DOEN].
     */
    public function draw_example() {
        return file_get_contents(__DIR__ . "/example_output.html");
    }

    /**
     * OUTPUT: An html string representing the day given in $target_day and the
     * tags given in $meta_data.
     */
    private function make_day_html($target_timestamp) {
        $cell_class = $this->meta_data["cell_class"];
        if($target_timestamp === $this->target_day_timestamp) {
            $cell_class = "calendar--today";
        }
        $day_of_month = date("d", $target_timestamp);
        return  "<calendar-cell id='" . $this->meta_data["cell_id"] .
                "' data-date='" . $this->meta_data["data_date"] .
                "' class='" . $cell_class .
                    "'><div class='date'>$day_of_month</div>
                    <div class='calendar--events'></div>
                    <div class='calendar--meta'></div>
                </calendar-cell>";
    }

    /**
     * OUTPUT: An html string representing the week given in $target_day and the
     * tags given in $meta_data.
     */
    private function make_week_html($target_timestamp) {
        $week_start_offset = 0 - date("w", $target_timestamp);
        $week_cells = "";
        for($i = 0; $i < 7; $i++) {
            $day_to_draw = strtotime("+$week_start_offset day", $target_timestamp);
            $week_cells .= $this->make_day_html($day_to_draw);
            $week_start_offset++;
        }
        return "<calendar-week data-week-of-year='13'>" . $week_cells . "</calendar-week>";
    }

    /**
     * OUTPUT: An html string representing the month given in $target_day and the
     * tags given in $meta_data.
     */
    private function make_month_html($target_timestamp) {
        //Calculate the starting week for the month.
        $start_of_week_offset = date("w", $target_timestamp);
        $current_week_to_draw = $target_timestamp;
        $current_week_start_day = date("d", $target_timestamp) - $start_of_week_offset;
        while($current_week_start_day > 1) {
            $current_week_to_draw = strtotime("-1 week", $current_week_to_draw);
            $current_week_start_day -= 7;
        }
        //Make the rows. Check that we are in the same month before drawing the next.
        $month_rows = "";
        // $same_month = true;
        // while($same_month) {
        //     $month_rows .= $this->make_week_html($current_week_to_draw);
        //     $current_week_to_draw = strtotime("+1 week", $current_week_to_draw);
        //     if(date("n", $current_week_to_draw) !== date("n", $target_timestamp)) {
        //         $same_month = false;
        //     }
        // }

        for($i = 0; $i < 6; $i++) {
            $month_rows .= $this->make_week_html($current_week_to_draw);
            $current_week_to_draw = strtotime("+1 week", $current_week_to_draw);
        }
        return $month_rows;
    }
}