<?php

namespace Calendar;

/**
 * This class is a representation of a Gregorian calendar. It can display a day,
 * week, or month focused around a specified day.
 * 
 * @author Ethan <ethan@heavyelement.io>
 */
class Calendar {
    /** @var int $timestamp_input The target timestamp this calendar draws itself around */
    private $timestamp_input;

    /**
     * Constructs a calendar with a target date.
     * 
     * @param int|string $date as UNIX TIMESTAMP | "Y-m-d" | "d-m-Y".
     */
    public function __construct($date) {
        $this->set_timestamp($date);
    }

    /**
     * @return int Returns the currently stored value of $timestamp_input.
     */
    public function get_timestamp() {
        return $this->timestamp_input;
    }

    /**
     * Properly stores a value for $timestamp_input with correct formating.
     * 
     * @param int|string $date as UNIX TIMESTAMP | "Y-m-d" | "d-m-Y".
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
     * Draws a calander with the correct number of day cells to represent a day,
     * week, or month.
     * 
     * @param string $type Type of calendar to draw as "day" | "week" | "month".
     * @param bool $month_changes TRUE if month can be changed | FALSE if not.
     * 
     * @return string A string of html representing a calendar.
     */
    public function draw($type = "month", $month_changes = TRUE) {
        $type = strtolower($type);
        $month_class = "calendar--current-month";
        if(date("Y-m", $this->timestamp_input) != date("Y-m", time())) {
            $month_class = "calendar--other-month";
        }
        $output = $this->make_title_headline_html($type, $month_changes) . "<calendar-table class='$month_class'>";

        if($type === "day") {
            $week_of_year = date("W", $this->timestamp_input);
            return $output . $this->make_week_header_html(false) .
                    "<calendar-week data-week-of-year='$week_of_year'>" .
                        $this->make_day_html($this->timestamp_input) .
                    "</calendar-week>
                </calendar-table>";
        }

        $output .= $this->make_week_header_html();

        if($type === "week") {
            return $output . $this->make_week_html($this->timestamp_input) . "</calendar-table>";
        }
        
        return $output . $this->make_month_html($this->timestamp_input) . "</calendar-table>";
    }

    /**
     * @param int $timestamp The timestamp to check.
     * 
     * @return bool TRUE if valid unix timestamp | FALSE if not valid unix timestamp.
     */
    private function is_timestamp($timestamp) {
        return ((string)(int)$timestamp === $timestamp) &&
            ($timestamp <= PHP_INT_MAX) && ($timestamp >= ~PHP_INT_MAX);
    }

    /**
     * @param int $timestamp The timestamp to be made uniform. All uniform timstamps
     * share the same time of day.
     * 
     * @return int The uniform timestamp.
     */
    private function make_timestamp_uniform($timestamp) {
        return strtotime(date("Y-m-d", $timestamp));
    }

    /**
     * @param string $type The string "day" | "week" | "month".
     * @param bool $month_changes TRUE if month can be changed | FALSE if not.
     * 
     * @return string A string of html representing the headline of the calendar.
     */
    private function make_title_headline_html($type = "month", $month_changes = TRUE) {
        $anchors = ["", ""];
        if($month_changes) {
            $type = strtolower($type);

            //Establish the targets.
            $last_date = date("Y-m-d", strtotime("last $type", $this->timestamp_input));
            $next_date = date("Y-m-d", strtotime("next $type", $this->timestamp_input));
            $target = date("Y-m-d", $this->timestamp_input);

            //Find the targets.
            $index = array_search($target, array_values($_GET['uri']));
            $key = array_keys($_GET['uri'])[$index];

            //Set the targets. Handle if there is no date in the URL.
            $last_url = str_replace($_GET['uri'][$key], $last_date, $_SERVER['REQUEST_URI']);
            $next_url = str_replace($_GET['uri'][$key], $next_date, $_SERVER['REQUEST_URI']);
            if($last_url === $_SERVER['REQUEST_URI']) {
                if(substr($_SERVER['REQUEST_URI'], -1) != "/") {
                    $last_url .= "/";
                    $next_url .= "/";
                }
                $last_url .= $last_date;
                $next_url .= $next_date;
            }
            $anchors = ["<a href='$last_url'>Last $type</a>",
                        "<a href='$next_url'>Next $type</a>"];
        }
        return "<div class='calendar--headline'>" .
                    $anchors[0] .
                    "<h2>" . date("F Y", $this->timestamp_input) . "</h2>" .
                    $anchors[1] .
                "</div>";
    }

    /**
     * @param bool $is_week TRUE if the calendar is drawing an entire week of cells |
     * FALSE if the calendar is drawing just one day cell of the calendar.
     * 
     * @return string A string of html representing the header of the calendar.
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
     * @param string $timestamp The target days timestamp.
     * 
     * @return string A string of html representing the given day.
     */
    private function make_day_html($timestamp) {
        $id = date("M-d", $timestamp);
        $data_unix = $timestamp;
        $class = "";
        $today = $this->make_timestamp_uniform(time());

        //Day number output.
        $day_of_month = date("d", $timestamp);

        //Other month.
        if(date("M", $timestamp) !== date("M", $this->timestamp_input)) {
            $class .= " calendar--other-month";
            $day_of_month = date("M d", $timestamp);
        }

        //Past days.
        if($timestamp < $today) {
            $class .= " calendar--past";
        }

        //Yesterday.
        if($timestamp === strtotime("yesterday", $today)) {
            $class .= " calendar--yesterday";
        }

        //Today.
        if($timestamp == $today) {
            $class .= " calendar--today";
        }

        //Tomorrow.
        if($timestamp === strtotime("tomorrow", $today)) {
            $class .= " calendar--tomorow";
        }
        
        //Target day.
        if($timestamp == $this->timestamp_input) {
            $class .= " calendar--target-date";
        }

        return  "<calendar-cell id='$id' data-unix-timestamp='$data_unix' class='$class'>
                    <div class='date'>$day_of_month</div>
                    <div class='calendar--events'></div>
                    <div class='calendar--meta'></div>
                </calendar-cell>";
    }

    /**
     * @param string $timestamp The target days timestamp.
     * 
     * @return string A string of html representing the given week.
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
     * @param string $timestamp The target days timestamp.
     * 
     * @return string A string of html representing the given month.
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