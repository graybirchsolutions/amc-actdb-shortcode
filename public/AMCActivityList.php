<?php

/**
 * Class to render an XML list of AMC trips as HTML
 *
 * @link       https://graybirch.solutions
 * @since      1.0.0
 *
 * @package    AMC_activities_shortcode
 * @subpackage AMC_activities_shortcode/public
 * @author     Martin Jensen <marty@graybirch.solutions>
 */

namespace AMCActivities\FrontEnd;

use SimpleXMLElement;

class AMCActivityList
{

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      SimpleXMLElement $amc_activities - A list of activities in XML format.
     */
    private $amc_activities;

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $html_string - HTML formatted version of the activities list.
     */
    private $html_string;


    public function __construct($string)
    {
        $this->amc_activities = new SimpleXMLElement($string);
        $this->html_string = '';
    }

    /**
     * function render_placeholder
     * 
     * Renders a placeholder block wrapped in a div with data tags to control the event query
     * through the API via javascript in the browser.
     * 
     * @since 2.0
     */
    public function render_placeholder($chapter, $committee, $activity, $limit)
    {
        // Wrap the event list
        $this->html_string = "<div ";
        $this->html_string .= "class=\"amc-events-container\" ";      // assign the class
        $this->html_string .= "data-chapter=\"$chapter\" ";           // Chapter data attribute
        $this->html_string .= "data-committee=\"$committee\" ";       // Committee data attribute
        $this->html_string .= "data-activity=\"$activity\" ";         // Activity data attribute
        $this->html_string .= "data-limit=\"$limit\" ";               // Limit data attribute
        $this->html_string .= ">\n";

        // Add loader div
        $this->html_string .= "    <div class=\"amc-loader\"></div>\n";

        // Close wrapping Div
        $this->html_string .= "</div>\n";

        return $this->html_string;
    }

    /**
     * @deprecated Events rendered via javascript in the browser since 2.0
     */
    public function render_list($display, $limit)
    {

        // Wrap the event list
        $this->html_string .= "<div class=\"amc-events-container\">\n";

        if ($this->amc_activities->getName() == 'errors') {
            $this->html_string = <<<'EOD'
<div class="amc-events-container">
  <div class="amc-event-wrap">
    <div class="amc-event-desc-block">
      <div class="amc-event-title">Sorry!</div>
      <div class="amc-event-description">
        No upcoming events are listed in the AMC Activities Calendar. Please
        check back frequently as we are often adding new trips and events to
        the calendar.
      </div>
    </div>
  </div>
</div>
EOD;
            // Close the event list wrap
            $this->html_string .= "</div>\n";
            return $this->html_string;
        }

        $i = 1;
        foreach ($this->amc_activities->trip as $event) {
            //      $this->html_string .= "<p>" . (string)$amctrip->trip_title . "</p>\n";

            if ($display == 'short') {
                $this->render_event_short($event);
            } elseif ($display == 'long') {
                $this->render_event_short($event);
            } else {
                $this->html_string .= "Invalid format: $display\n";
                return $this->html_string;
            }

            if ($i++ == (int)$limit) break;
        }

        // Close the event list wrap
        $this->html_string .= "</div>\n";

        return $this->html_string;
    }

    /**
     * @deprecated Events rendered via javascript in the browser since 1.1.0
     */
    private function render_event_short($event)
    {
        $event_date = strtotime($event->trip_start_date);
        $status_class = '';

        // Open event wrap with .amc-event-wrap
        $this->html_string .= "  <div class=\"amc-event-wrap amc-event-short\">\n";

        // Render the event

        // Render date block
        $this->html_string .= $this->render_date_block($event_date);

        // Open description wrap with .amc-event-desc-block
        $this->html_string .= "<div class=\"amc-event-desc-block\">\n";

        // Render the event title with a link back to the event on the AMC website
        $this->html_string .= "<span class=\"amc-event-title\">" .
            "<a href=\"https://activities.outdoors.org/search/index.cfm/action/details/id/" .
            (string)$event->trip_id . "\">" .
            (string)$event->trip_title . "</a></span>\n";

        // Wrap description info
        $this->html_string .= "<div class=\"amc-event-desc\">";

        // Wrap lead line - Date and Status
        $this->html_string .= "<div class=\"amc-event-desc-lead\">";

        // Render date. If time 0000 (midnight) then ignore time else display time
        $this->html_string .= "<span class=\"amc-event-date\">" . date("D M j Y", $event_date);
        if (date("Hi", $event_date) != "0000") {
            $this->html_string .= date(', \a\t g:i a', $event_date);
        }
        $this->html_string .= "</span>\n";

        // Render event status
        $this->html_string .= "<span class=\"amc-event-status\"><span class=\"key\">Status</span>: ";
        switch ($event->status) {
            case "Open":
                $status_class = "amc-status-open";
                break;
            case "Canceled":
                $status_class = "amc-status-canceled";
                break;
            case "Wait Listed":
                $status_class = "amc-status-waitlist";
                break;
        }
        $this->html_string .= "<span class=\"" . $status_class . "\">" . $event->status . "</span>";

        // Close lead line (.amc-event-desc-lead)
        $this->html_string .= "</div>\n";

        // Wrap info line - Activity, level and leader
        $this->html_string .= "<div class=\"amc-event-desc-info\">";

        // Render the event type and event level (if present)

        $i = 0;
        foreach ($event->activities->activity as $type) {
            $this->html_string .= "<span class=\"amc-event-type\"><span class=\"key\">Activity</span>: " . $type . " ";
            if ($i++ == 0 && $event->tripDifficulty != '') {
                $this->html_string .= "<span class=\"amc-event-level\">(<span class=\"key\">Level</span>: " . $event->tripDifficulty . ")</span>";
            }
            $this->html_string .= "</span>\n";
        }


        // Render the leader information. Include email if present.
        $this->html_string .= "<span class=\"amc-event-leader\"><span class=\"key\">Leader</span>: " . (string)$event->leader1;
        if ((string)$event->leader1_email != '') {
            $this->html_string .= " <<a href=\"mailto:" . (string)$event->leader1_email . "\">" .
                (string)$event->leader1_email . "</a>>";
        }
        $this->html_string .= "</span>\n";

        // Close info line (.amc-event-desc-info)
        $this->html_string .= "</div>\n";

        // Render the location if present
        if ($event->trip_location != '') {
            $this->html_string .= "<div class=\"amc-event-location\"><span class=\"key\">Location</span>: " .
                (string)$event->trip_location . "</div>\n";
        }

        // Close description info (.amc-event-desc-info)
        $this->html_string .= "</div>\n";

        // Close description wrap (.amc-event-desc-block)
        $this->html_string .= "</div>\n";

        // Close event wrap (.amc-event-wrap)
        $this->html_string .= "</div>\n";
        return;
    }

    /**
     * @deprecated Events rendered via javascript in the browser since 1.1.0
     */
    private function render_event_long($event)
    {
    }

    /**
     * Function to render the date block from the event date
     *
     * @since    1.0.0
     * @access   private
     * @var      Timestamp $evdate - Unix Timestamp with the event date.
     *
     */

    /**
     * @deprecated Events rendered via javascript in the browser since 1.1.0
     */
    private function render_date_block($evdate)
    {

        // Open date block wrap
        $dbstring = "<div class=\"amc-date-block\">\n";

        // Open start date block
        $dbstring .= "  <span class=\"amc-start-date\">\n";

        // Render the date
        $dbstring .= "    <span class=\"date\">" . date("d", $evdate) . "</span>\n";
        $dbstring .= "    <span class=\"month\">" . date("M", $evdate) . "</span>\n";
        $dbstring .= "    <span class=\"year\">" . date("Y", $evdate) . "</span>\n";

        // Close start date
        $dbstring .= "  </span>\n";

        // Close date block
        $dbstring .= "</div>\n";

        return $dbstring;
    }
}
