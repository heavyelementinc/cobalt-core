<?php

namespace Calendar;

class Calendar {
    private $timestamp_input;

    /**
     * Constructs the Calendar and stores the input date uniformly as a unix timestamp.
     * 
     * INPUT: $date as UNIX TIMESTAMP | "YYYY-MM-DD" | "DD-MM-YYYY".
     */
    public function __construct($date) {
        $this->set_timestamp($date);
    }

    /**
     * OUTPUT: 
     */
    public function get_timestamp() {
        return $timestamp_input;
    }

    /**
     * INPUT: 
     */
    public function set_timestamp($date) {
        $this->timestamp_input = $date;
        if(!$this->is_timestamp($date)) {
            $this->timestamp_input = strtotime($date);
            if(!strtotime($date)) {
                $this->timestamp_input = time();
            }
        }
        $this->timestamp_input = $this->make_timestamp_uniform($this->timestamp_input);
    }

    /**
     * INPUT: 
     * 
     * OUTPUT: 
     */
    public function draw($type = "month") {
        $type = strtolower($type);
        $output = $this->make_title_headline_html() . "<calendar-table class='calendar--current-month'>";

        if($type === "day") {
            $week_of_year = date("W", $this->timestamp_input);
            return $output . $this->make_week_header_html(false) .
                    "<calendar-week data-week-of-year='$week_of_year'>" .
                        $this->make_day_html($this->timestamp_input) .
                    "</calendar-week>
                </calendar-table>";;
        }

        $output .= $this->make_week_header_html();

        if($type === "week") {
            return $output . $this->make_week_html($this->timestamp_input) . "</calendar-table>";
        }
        
        return $output . $this->make_month_html($this->timestamp_input) . "</calendar-table>";
    }

    /**
     * OUTPUT: TRUE if valid unix timestamp | False if not valid unix timestamp.
     */
    private function is_timestamp($timestamp) {
        return((string)(int)$timestamp === $timestamp) &&
            ($timestamp <= PHP_INT_MAX) && ($timestamp >= ~PHP_INT_MAX);
    }

    /**
     * INPUT: 
     * 
     * OUTPUT: 
     */
    private function make_timestamp_uniform($timestamp) {
        return strtotime(date("Y-m-d", $timestamp));
    }

    /**
     * INPUT: The string "day" | "week" | "month".
     * 
     * OUTPUT: 
     */
    private function make_title_headline_html($type = "month") {
        $type = strtolower($type);
        //////THIS DOESNT WORK!!!
        if(!$type === "day" || !$type === "week" || !$type === "month")
            return "[Error: Invalid input to make_title_headline_html().]";
        $last = date("Y-m-d", strtotime("last $type", $this->timestamp_input));
        $next = date("Y-m-d", strtotime("next $type", $this->timestamp_input));
        return "<div class='calendar--headline'>
                    <a href='http://dev.he/debug/calendar/$last'>Last $type</a>
                    <h2>" . date("F Y", $this->timestamp_input) . "</h2>
                    <a href='http://dev.he/debug/calendar/$next'>Next $type</a>
                </div>";
    }

    /**
     * INPUT: 
     * 
     * OUTPUT: 
     */
    private function make_week_header_html($is_week = true) {
        $week_header_html = ["<calendar-header>Sunday</calendar-header>",
                            "<calendar-header>Monday</calendar-header>",
                            "<calendar-header>Tuesday</calendar-header>",
                            "<calendar-header>Wednesday</calendar-header>",
                            "<calendar-header>Thursday</calendar-header>",
                            "<calendar-header>Friday</calendar-header>",
                            "<calendar-header>Saturday</calendar-header>"];

        $header = "<calendar-week class='calendar--header' data-week-of-year='header'>";
        if($is_week) return $header . implode("", $week_header_html) . "</calendar-week>";
        return $header . $week_header_html[date("w", $this->timestamp_input)] . "</calendar-week>";
    }

    /**
     * OUTPUT: An html string representing the day given in $target_day and the
     * tags given in $meta_data.
     */
    private function make_day_html($timestamp) {
        $id = date("M-d", $timestamp);
        $data_unix = $timestamp;
        $class = "";
        $past_month_check = 0; //Value set to 2 when its valid.

        //Other month.
        if(date("M", $timestamp) !== date("M", $this->timestamp_input)) {
            $class .= " calendar--other-month";
            $past_month_check++;
        }

        //Past days.
        if($timestamp < $this->timestamp_input) {
            $class .= " calendar--past";
            $past_month_check++;
        }

        //Yesterday.


        //Today.
        if($timestamp == $this->timestamp_input) {
            $class .= " calendar--today";
        }

        //Tomorrow.

        
        //Target day.
        if($timestamp == $this->timestamp_input) {
            $class .= " calendar--target-date";
        }
        
        //Day output.
        $day_of_month = date("d", $timestamp);
        if($past_month_check === 2) {
            $day_of_month = date("M", $timestamp) . " " . $day_of_month;
        }

        return  "<calendar-cell id='$id' data-unix-timestamp='$data_unix' class='$class'>
                    <div class='date'>$day_of_month</div>
                    <div class='calendar--events'></div>
                    <div class='calendar--meta'></div>
                </calendar-cell>";
    }

    /**
     * OUTPUT: An html string representing the week given in $target_day and the
     * tags given in $meta_data.
     */
    private function make_week_html($timestamp) {
        $week_start_offset = 0 - date("w", $timestamp);
        $week_cells = "";
        for($i = 0; $i < 7; $i++) {
            $day_to_draw = strtotime("+$week_start_offset day", $timestamp);
            $week_cells .= $this->make_day_html($day_to_draw);
            $week_start_offset++;
        }
        $week_of_year = date("W", $this->timestamp_input);
        return "<calendar-week data-week-of-year='$week_of_year'>" . $week_cells .
            "</calendar-week>";
    }

    /**
     * OUTPUT: An html string representing the month given in $target_day and the
     * tags given in $meta_data.
     */
    private function make_month_html($timestamp) {
        $week_to_draw = $timestamp;
        $week_start_offset = date("w", $timestamp);

        //Calculate and set the starting week of the month.
        $day_of_month = date("d", $timestamp) - $week_start_offset;
        while($day_of_month > 1) {
            $week_to_draw = strtotime("-1 week", $week_to_draw);
            $day_of_month -= 7;
        }

        //Calculate the number of weeks in the month.
        $current_month = date("F", $timestamp);
        $current_year = date("Y", $timestamp);
        $month_start_offset = date("w", strtotime("1 $current_month $current_year"));
        $num_weeks = (date("t", $timestamp) + $month_start_offset) / 7;

        //Draw each week of the month.
        $month_rows = "";
        for($i = 0; $i < $num_weeks; $i++) {
            $month_rows .= $this->make_week_html($week_to_draw);
            $week_to_draw = strtotime("+1 week", $week_to_draw);
        }

        return $month_rows;
    }
}