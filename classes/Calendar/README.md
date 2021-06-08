# Calendar
## About
This calendar class is designed to be a simple and effective representation of the
Gregorian calendar. Upon instantiation the user can specify a target date for the
calendar to render itself around. If no date is given, the curent date is used as 
a default. The user can then call the "render()" function to display the calendar.
The size of the calendar can be set to a single day, a week, or an entire month.
Additionally the user can choose to display buttons that allow the calendar to 
switch to the previous or next set of days. If the target day of the calendar needs
to be changed the user can do so by calling the "set_timestamp($date)" function.
They will then have to re-render the calendar to show the new results of the date
change.

---

## Public Functions
* __construct($date)
* get_timestamp()
* set_timestamp($date)
* get_calendar_type()
* get_first_cell_timestamp()
* get_last_cell_timestamp()
* render($type = "month", $month_changes = TRUE)

---

## Examples
### Constructor...
```php
//Create a calendar instance with todays date as the target.
$calendar = new \Calendar\Calendar();

//Create a calendar instance with $date as the target date.
$date = "2021-05-12"; //Also excepts "d-m-Y" and unix timestamp formats.
$calendar = new \Calendar\Calendar($date);
```

### Getters and Setters...
```php
//Gets the currently stored timestamp.
$calendar->get_timestamp();

//Sets the target date for the calendar to draw itself around.
$date = "2021-05-12"; //Also excepts "d-m-Y" and unix timestamp formats.
$calendar->set_timestamp($date);
$calendar->render(); //Dont forget to re-render the calendar to show new results.

//Get the type of calendar currently rendered.
$calendar->get_calendar_type();

//Get the first cell currently rendered for the calendar.
$calendar->get_first_cell_timestamp()

//Get the last cell currently rendered for the calendar.
$calendar->get_last_cell_timestamp()
```

### Display the calendar...
```php
//Display a month calendar that can switch to a different month.
$calendar->render();

//Display a day calendar that can not switch to a different month.
$calendar->render("day", FALSE);

//Display a week calendar that can switch to a different month.
$calendar->render("week");
```

---

## Author
Ethan | <ethan@heavyelement.io><br>
Software Engineer | [Heavy Element, Inc](https://heavyelement.io/)

---