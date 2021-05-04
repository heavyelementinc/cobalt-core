<?php

namespace Calendar;

class Calendar {
    private $today_timestamp;
    private $target_day_timestamp;
    private $meta_data;

    /**
     * Constructs the Calendar and stores the input date uniformly as a unix timestamp.
     * You can specify the meta data for the styling or use the defaults.
     * 
     * INPUT: $date_stamp as UNIX TIMESTAMP | "YYYY-MM-DD".
     * INPUT: 
     */
    public function __construct($date_stamp, $meta_data = []) {
        $this->target_day_timestamp = $date_stamp;
        if(!$this->check_if_timestamp($date_stamp)) {
            $this->target_day_timestamp = strtotime($date_stamp);
            if(!strtotime($date_stamp)) {
                $this->target_day_timestamp = time();
            }
        }

        $meta_data = array_merge(["day_id"=>"", "data_date"=>"", "day_class"=>"",
            "week_class"=>"", "data_week_of_year"=>"", "month_class"=>""], $meta_data);
        $this->meta_data = $meta_data;
    }

    /**
     * INPUT: 
     * 
     * OUTPUT: 
     */
    public function draw($type = "month") {
        $type = strtolower($type);
        $output = $this->make_title_header_html() . "<calendar-table class='calendar--current-month'>";

        if($type === "day") {
            return $output . $this->make_week_header_html(false) .
                    "<calendar-week data-week-of-year='13'>" .
                        $this->make_day_html($this->target_day_timestamp) .
                    "</calendar-week>
                </calendar-table>";;
        }

        $output .= $this->make_week_header_html();

        if($type === "week") {
            return $output . $this->make_week_html($this->target_day_timestamp) . 
            "</calendar-table>";
        }
        
        return $output . $this->make_month_html($this->target_day_timestamp) .
            "</calendar-table>";
    }

    /**
     * OUTPUT: Example HTRML [DELETE THIS WHEN DONE].
     */
    public function draw_example() {
        return file_get_contents(__DIR__ . "/example_output.html");
    }

    /**
     * OUTPUT: TRUE if valid unix timestamp | False if not valid unix timestamp.
     */
    private function check_if_timestamp($timestamp) {
        return((string)(int)$timestamp === $timestamp) &&
            ($timestamp <= PHP_INT_MAX) && ($timestamp >= ~PHP_INT_MAX);
    }

    /**
     * OUTPUT: 
     */
    private function make_title_header_html() {
        return "<h2 class='calendar--headline'>" . date("F", $this->target_day_timestamp) .
            " " . date("Y", $this->target_day_timestamp) . "</h2>";
    }

    /**
     * INPUT: 
     * 
     * OUTPUT: 
     */
    private function make_week_header_html($whole_week = true) {
        $week_header_html = ["<calendar-header>Sunday</calendar-header>",
                            "<calendar-header>Monday</calendar-header>",
                            "<calendar-header>Tuesday</calendar-header>",
                            "<calendar-header>Wednesday</calendar-header>",
                            "<calendar-header>Thursday</calendar-header>",
                            "<calendar-header>Friday</calendar-header>",
                            "<calendar-header>Saturday</calendar-header>"];

        $header = "<calendar-week class='calendar--header' data-week-of-year='header'>";
        if($whole_week) return $header . implode("", $week_header_html) . "</calendar-week>";
        return $header . $week_header_html[date("w", $this->target_day_timestamp)] . "</calendar-week>";
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
        $week_to_draw = $target_timestamp;
        $week_start_offset = date("w", $target_timestamp);

        //Calculate and set the starting week of the month.
        $day_of_month = date("d", $target_timestamp) - $week_start_offset;
        while($day_of_month > 1) {
            $week_to_draw = strtotime("-1 week", $week_to_draw);
            $day_of_month -= 7;
        }

        //Calculate the number of weeks in the month.
        $current_month = date("F", $target_timestamp);
        $current_year = date("Y", $target_timestamp);
        $month_start_offset = date("w", strtotime("1 $current_month $current_year"));
        $num_weeks = (date("t", $target_timestamp) + $month_start_offset) / 7;

        //Draw each week of the month.
        $month_rows = "";
        for($i = 0; $i < $num_weeks; $i++) {
            $month_rows .= $this->make_week_html($week_to_draw);
            $week_to_draw = strtotime("+1 week", $week_to_draw);
        }

        return $month_rows;
    }
}