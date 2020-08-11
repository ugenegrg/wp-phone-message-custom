<?php
if ( ! class_exists( 'WpPhoneMessageShortcode' ) ) {

	class WpPhoneMessageShortcode {

		public $today_or_tomorrow = '';
		public $preparation_time_flag = true;

		public function __construct() {
			add_action( 'init', array( $this, 'registerShortcode' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'shortcodeStyle' ) );

			add_action( 'wp_ajax_get_hrs', array( $this, 'get_day_hrs' ) );
			add_action( 'wp_ajax_nopriv_get_hrs', array( $this, 'get_day_hrs' ) );

//			date_default_timezone_set('Asia/Kathmandu');
			date_default_timezone_set('Europe/Madrid');

		}

		public function registerShortcode() {
			add_shortcode( 'wp-phone-message', array( $this, 'renderShortcode' ) );
		}

		public function renderShortcode( $atts ) {
			include_once PLUGIN_WPM_PATH . 'views/shortcode-form.php';

			return $shortcode_form;
		}

		public function shortcodeStyle() {
			wp_enqueue_style( 'wp-phone-message-shortcode', PLUGIN_WPM_URL . 'css/shortcode.min.css', array(), null, 'all' );
//			wp_enqueue_script( 'wp-phone-message-shortcode', PLUGIN_WPM_URL . 'js/shortcode.min.js', array( 'jquery' ), '1.0.0', true );
			wp_enqueue_script( 'wp-phone-message-shortcode', PLUGIN_WPM_URL . 'js/shortcode.js', array( 'jquery' ), '1.0.0', true );
			wp_localize_script(
				'wp-phone-message-shortcode',
				'wp_phone_message_obj',
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
				)
			);
		}

		public function match_days( $dynamic_day ) {

			$static_days = array(
				'Monday'    => 'Dilluns',
				'Tuesday'   => 'Dimarts',
				'Wednesday' => 'Dimecres',
				'Thursday'  => 'Dijous',
				'Friday'    => 'Divendres',
				'Saturday'  => 'Disabte',
				'Sunday'    => 'Diumenge',

			);

			return $static_days[ $dynamic_day ];
		}

		public function define_hrs( $post_id, $start_time, $end_time ) {

			if ( "today" == $this->today_or_tomorrow ) {
				$start_time = date( 'Y-m-d' ) . ' ' . $start_time;
				$end_time   = date( 'Y-m-d' ) . ' ' . $end_time;

			}
			else { // if tomorrow

				// date( 'l', strtotime( '+1 day' ) );
				$start_time = date( 'Y-m-d', strtotime('+1 day') ) . ' ' . $start_time;
				$end_time   = date( 'Y-m-d', strtotime('+1 day') ) . ' ' . $end_time;
			}

			$time_array = array();

			// current time.
			$current_time = strtotime( date( 'Y-m-d H:i' ) );

			// convert all time to string.
			$str_start_time = strtotime( $start_time );
			$str_end_time   = strtotime( $end_time );
			$str_inc_time   = strtotime( $start_time );

			// consider preparation time.
			$preparation_time = '+30 minutes'; // default
			if ( get_field( 'temps_preparacio', $post_id ) ) {
				$preparation_time = get_field( 'temps_preparacio', $post_id, true ); // in minutes.
				$preparation_time = '+' . $preparation_time . ' minutes';
			}

			// current time + preparation time.
			// if the order is for tomorrow - no need to add preparation delay time.
			// this preparation_time_flag is set in get_day_hrs()
			$ready_time = $current_time;
			if ( $this->preparation_time_flag ) {
				$ready_time = strtotime( $preparation_time, $current_time ); // strtotime('+59 minutes', strtotime('2011-11-17 05:05'));
			}

			// set interval.
			$interval = '+30 minutes';
			if ( get_field( 'interval', $post_id ) ) {
				$inteval  = get_field( 'interval', $post_id );
				$interval = '+' . $inteval . ' minutes';
			}

			// create hrs array with interval.
			// make sure the $str_inc_time < $str_end_time
			// and also $str_inc_time > $ready_time
			while ( ( $str_inc_time <= $str_end_time ) ) {
				if( $str_inc_time > $ready_time ) {
					$time_array[] = date( 'H:i', $str_inc_time );
				}
				$str_inc_time = strtotime( $interval, $str_inc_time );
			}

			return $time_array;
		}

		public function get_day_hrs() {

			// check nonce
			if ( ! wp_verify_nonce( $_POST['nonce'], 'get_hrs' ) ) {
				return;
			}

			$day     = $_POST['day'];
			$post_id = $_POST['post_id'];

			if ( $day == 'avui' ) { // case of today

				$day_name                = date( 'l' );
				$this->today_or_tomorrow = 'today';

			} elseif ( $day == 'dema' ) { // case of tomorrow
				$this->today_or_tomorrow     = 'tomorrow';
				$this->preparation_time_flag = false;
				$day_name                    = date( 'l', strtotime( '+1 day' ) );
			}

			$matched_day = $this->match_days( $day_name ); // matched day name is the ACF field value in the backend.
			$day         = strtolower( $matched_day ); // use this to matched the acf field group array.

			$pick_up_times = get_field( 'horari_recollida', $post_id );

			$pick_up_times = $pick_up_times[ $day ];

			// early morning time
			$pick_up_times_early_start = $pick_up_times[ $day . '_mati_inici' ];
			$pick_up_times_early_end   = $pick_up_times[ $day . '_mati_final' ];

			// late afternoon time
			$pick_up_times_late_start = $pick_up_times[ $day . '_tarde_inici' ];
			$pick_up_times_late_end   = $pick_up_times[ $day . '_tarde_final' ];

			// check if it is all day hrs or
			// the hrs is divided as early / late
//			if( "" == $pick_up_times_early_end && "" == $pick_up_times_late_start ) { // meaning - it is a full day hrs schedule
//
//				// should be array of hrs
//				$full_hrs = $this->define_hrs( $post_id, $pick_up_times_early_start, $pick_up_times_late_end );
//				$full_options = $this->make_option_out_of_available_hrs( 'Horas', $full_hrs );
//				$return = array(
//					'success' => true,
//					'options' => $full_options
//				);
//
//			} else { // meaning - it the hrs are divided into morning and evening
//
//				// should be array of hrs
//				$early_hrs = $this->define_hrs( $post_id, $pick_up_times_early_start, $pick_up_times_early_end );
//				$late_hrs  = $this->define_hrs( $post_id, $pick_up_times_late_start, $pick_up_times_late_end );
//
//				// $all_available_hrs_for_day = array_merge( $early_hrs, $late_hrs );
//				$early_options = $this->make_option_out_of_available_hrs( 'Matí', $early_hrs );
//				$late_options  = $this->make_option_out_of_available_hrs( 'Tarde', $late_hrs );
//
//				$return = array(
//					'success' => true,
//					'options' => $early_options . $late_options
//				);
//			}

			// should be array of hrs
			$early_hrs = $this->define_hrs( $post_id, $pick_up_times_early_start, $pick_up_times_early_end );
			$late_hrs  = $this->define_hrs( $post_id, $pick_up_times_late_start, $pick_up_times_late_end );

			// $all_available_hrs_for_day = array_merge( $early_hrs, $late_hrs );
			$early_options = $this->make_option_out_of_available_hrs( 'Matí', $early_hrs );
			$late_options  = $this->make_option_out_of_available_hrs( 'Tarde', $late_hrs );

			$return = array(
				'success' => true,
				'options' => $early_options . $late_options
			);

			echo json_encode( $return );
			wp_die();
		}

		public function make_option_out_of_available_hrs( $label, $hrs ) {

			$option = '<optgroup label="' . $label . '">';

			if ( ! empty( $hrs ) ) {
				foreach ( $hrs as $key => $value ) {
					$option .= '<option value="' . $value . '">' . $value . '</option>';
				}
			} else {
				$option .= '<option value="No hi ha opció" disabled>No hi ha opció</option>';
			}

			$option .= '</optgroup>';

			return $option;

		}
	}
}
