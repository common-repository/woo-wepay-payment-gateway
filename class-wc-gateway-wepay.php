<?php

class WC_Gateway_Wepay extends WC_Payment_Gateway {
	private $_WEPAY_API = null;

	public function __construct() {
		$this->_WEPAY_API   = new WEPAY_API();
		$this->id           = 'wepay';
		$this->has_fields   = false;
		$this->method_title = __( 'Wepay Payment', 'woocommerce' );

		//load the setting
		$this->init_form_fields();
		$this->init_settings();

		//Define user set variables
		$this->title                  = $this->get_option( 'title' );
		$this->description            = $this->get_option( 'description' );
		$this->wepay_site_code        = $this->get_option( 'wepay_site_code' );
		$this->wepay_secret_key       = $this->get_option( 'wepay_secret_key' );
		$this->wepay_payment_method   = $this->get_option( 'wepay_payment_method' );
		$this->icon                   = $this->get_option( 'icon' );
		$this->liveurl                = $this->get_option( 'wepay_api_url' );
		$this->form_submission_method = false;

		add_action( 'woocommerce_api_wc_gateway_wepay', array( $this, 'callback' ) );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array(
			$this,
			'process_admin_options'
		) );

		if ( ! $this->is_valid_for_use() ) {
			$this->enabled = false;
		}
	}

	/**
	 * Check if this gateway is enabled and available in the user's country
	 *
	 * @access public
	 * @return bool
	 */
	function is_valid_for_use() {
		if ( ! in_array( get_woocommerce_currency(), apply_filters( 'woocommerce_wepay_supported_currencies', array(
			'VND',
			'VNĐ'
		) ) ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Admin Panel Options
	 * - Options for bits like 'title' and availability on a country-by-country basis
	 *
	 * @since 1.0.0
	 */
	public function admin_options() {
		?>
        <h3><?php _e( 'Thanh toán Wepay', 'woocommerce' ); ?></h3>
        <strong><?php _e( 'Đảm bảo an toàn tuyệt đối cho mọi giao dịch.', 'woocommerce' ); ?></strong>
		<?php if ( $this->is_valid_for_use() ) : ?>

            <table class="form-table">
				<?php
				// Generate the HTML For the settings form.
				$this->generate_settings_html();
				?>
            </table><!--/.form-table-->

		<?php else : ?>
            <div class="inline error"><p>
                    <strong><?php _e( 'Gateway Disabled', 'woocommerce' ); ?></strong>: <?php _e( 'Phương thức thanh toán Wepay không hỗ trợ loại tiền tệ trên gian hàng của bạn.', 'woocommerce' ); ?>
                </p></div>
			<?php
		endif;
	}

	/**
	 * Initialise Gateway Settings Form Fields
	 *
	 * @access public
	 * @return void
	 */
	function init_form_fields() {

		$this->form_fields = array(
			'enabled'              => array(
				'title'   => __( 'Activate', 'woocommerce' ),
				'type'    => 'checkbox',
				'label'   => __( 'Kích hoạt cổng thanh toán Wepay cho WooComerce', 'woocommerce' ),
				'default' => 'yes'
			),
			'title'                => array(
				'title'       => __( 'Tiêu đề', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Tiêu đề của phương thức thanh toán bạn muốn hiển thị cho người dùng.', 'woocommerce' ),
				'default'     => __( 'Thanh toán trực tuyến qua wepay.vn', 'woocommerce' ),
				'desc_tip'    => true,
			),
			'icon'                 => array(
				'title'       => __( 'Logo Wepay', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'https://wepay.vn/images/logo-xs.png', 'woocommerce' ),
				'default'     => __( 'https://wepay.vn/images/logo-xs.png', 'woocommerce' ),
				'desc_tip'    => true,
			),
			'description'          => array(
				'title'   => __( 'Mô tả phương thức thanh toán', 'woocommerce' ),
				'type'    => 'textarea',
				'default' => __( 'Công cụ thanh toán điện tử WePay - Nhanh chóng, Tiện lợi, An Toàn', 'woocommerce' )
			),
			'wepay_api_url'        => array(
				'title'       => __( 'Url Api', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'URL API', 'woocommerce' ),
				'default'     => __( 'https://api.wepay.vn', 'woocommerce' ),
				'desc_tip'    => true,
			),
			'wepay_site_code'      => array(
				'title'       => __( 'Mã Site Code', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Site Code', 'woocommerce' ),
				'default'     => __( 'test', 'woocommerce' ),
				'desc_tip'    => true,
			),
			'wepay_secret_key'     => array(
				'title'       => __( 'Mã Secret key', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Mã Secret Key', 'woocommerce' ),
				'default'     => __( '123456789', 'woocommerce' ),
				'desc_tip'    => true,
			),
			'wepay_payment_method' => array(
				'title'       => __( 'Phương thức thanh toán' ),
				'type'        => 'select',
				'description' => __( 'Merchant lựa chọn các phương thức thanh toán bao gồm: thẻ visa master, thẻ nội địa hoặc cả 2', 'woocommerce' ),
				'default'     => '',
				'desc_tip'    => true,
				'options'     => array(
					''  => __( 'Thẻ quốc tế (Visa, Master) và Nội địa (ATM, IB)', 'woocommerce' ),
					'1' => __( 'Thẻ quốc tế (Visa, Master)', 'woocommerce' ),
					'2' => __( 'Thẻ nội địa (ATM, IB)', 'woocommerce' ),
				)
			)
		);

	}

	/**
	 * Process the payment and return the result
	 *
	 * @access public
	 *
	 * @param int $order_id
	 *
	 * @return array
	 */
	function process_payment( $order_id ) {
		$order = new WC_Order( $order_id );
		if ( ! $this->form_submission_method ) {
			$result = $this->get_wepay_args( $order );
			if ( ! empty( $result['error'] ) ) {
				echo '<p class="woocommerce-error"><strong>' . $result['error'] . '</strong></p></div>';
				die;
			}
			$wepay_url = $result['redirect_url'] ? $result['redirect_url'] : '';

			return array(
				'result'   => 'success',
				'redirect' => $wepay_url
			);
		} else {
			return array(
				'result'   => 'success',
				'redirect' => add_query_arg( 'order', $order_id, add_query_arg( 'key', $order->order_key, get_permalink( woocommerce_get_page_id( 'pay' ) ) ) )
			);
		}
	}

	function get_wepay_args( $order ) {
		$order_id    = time() . "-" . $order->id;
		$url_success = get_bloginfo( 'wpurl' ) . "/?wc-api=WC_Gateway_Wepay&action=order_received&order_id=" . $order_id;

		$order_mobile = strlen( $order->billing_phone ) != 0 ? $order->billing_phone : '09' . rand( '00000000', '99999999' );
		$order_info   = strlen( $order->customer_note ) != 0 ? $order->customer_note : "Thanh toán giỏ hàng {$order_id} từ queenpearlphuquoc.com; Tổng tiền: {$order->order_total}đ; Tên: {$order->billing_first_name} {$order->billing_last_name} - SĐT: {$order_mobile}";
		$params       = array(
			'site_code'    => strval( $this->wepay_site_code ),
			'url_message'  => $url_success,
			'order_info'   => $order_info,
			'order_code'   => strval( $order_id ),
			'order_price'  => strval( $order->order_total ),
			'order_email'  => strval( $order->billing_email ),
			'order_mobile' => strval( $order_mobile ),
			'payment_type' => strval( $this->wepay_payment_method ),
			'embed_type'   => 'desktop',
			'lang'         => 'vi',
			'version'      => '3',
		);

		// call api wepay
		$result = $this->_WEPAY_API->call_API( "POST", $params, '/bank_charge', $this );
		$result = json_decode( $result, true );

		return $result;
	}

	/**
	 * Điều hướng tác vụ xử lý cập nhật đơn hàng sau thanh toán
	 */
	function callback() {
		if ( ! empty( $_GET ) && isset( $_GET['action'] ) ) {
			switch ( $_GET['action'] ) {
				case 'order_received' :
					$this->order_received();
					break;
			}
		}
	}

	/**
	 * Hàm thực hiện kiểm tra đơn hàng và cập nhập trạng thái đơn hàng sau khi thanh toán tại wepay
	 */
	private function order_received() {
		global $woocommerce;
		if ( isset( $_GET['order_id'] ) && ! empty( $_GET['order_id'] ) ) {
			$str_id   = explode( "-", $_GET['order_id'] );
			$order_id = $str_id[1];
			if ( is_numeric( $order_id ) && $order_id > 0 ) :
				$order = new WC_Order( $order_id );
			else :
				die;
			endif;

			if ( empty( $order ) ) {
				die;
			}
			unset( $_GET['wc-api'] );
			unset( $_GET['action'] );

			$verify        = $this->_WEPAY_API->verifyReturnUrl( $this );
			$response_code = isset( $_GET["response_code"] ) ? $_GET["response_code"] : '';
			switch ( $_GET['payment_type'] ) {
				case 1:
					$respone_massage = $this->_WEPAY_API->getResponseDescriptionInternational( $response_code );
					break;
				case 2:
					$respone_massage = $this->_WEPAY_API->getResponseDescriptionDomestic( $response_code );
					break;
					$respone_massage = $_GET['response_message'];
				default;
			}

			// check du lieu
			if ( $verify ) {
				// check status
				$response = $this->_WEPAY_API->queryOrderStatus( $_GET['order_id'], $this->liveurl, $this );

				// giao dich thanh cong
				if ( $response_code == '0' && $response->shp_order_status == '1' && $response->shp_payment_response_code == '0' ) {
					$order->update_status( 'on-hold', __( $respone_massage, 'woocommerce' ) );
				} else {
					$order->update_status( 'failed', __( $respone_massage, 'woocommerce' ) );
				}

				$woocommerce->cart->empty_cart();
				unset( $_SESSION['order_awaiting_payment'] );
				wp_redirect( add_query_arg( 'utm_nooverride', '1', $this->get_return_url( $order ) ) );
			} else {
				$order->get_cancel_order_url();
			}
		}
	}
}

Class WEPAY_API {
	public function call_API( $method, $params, $api, $object ) {
		$wepay_secret_key = $object->wepay_secret_key;

		$api_url               = $object->liveurl . $api;
		$params['secure_hash'] = $this->_createHASH( $params, $wepay_secret_key );
		$response              = $this->_makeRequest( $api_url, $params, $method );

		if ( empty( $response ) ) {
			return array(
				'response_code' => 1,
				'error'         => 'khong goi duoc api wepay'
			);
		}

		return $response;
	}

	private function _createHASH( $data, $secret_key ) {
		ksort( $data );
		$md5HashData = "";
		foreach ( $data as $key => $value ) {
			if ( strlen( $value ) > 0 ) {
				$md5HashData .= $key . "=" . $value . "&";
			}
		}
		$md5HashData = rtrim( $md5HashData, "&" );
		$md5HashData = str_replace( '&amp;', '&', $md5HashData );

		return strtoupper( hash_hmac( 'SHA256', $md5HashData, pack( 'H*', $secret_key ) ) );
	}

	private function _makeRequest( $url, $params, $method = 'POST' ) {
		$ch = curl_init( $url );
		curl_setopt( $ch, CURLOPT_POST, true );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $params );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false );
		curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 60 ); // Time out 60s
		curl_setopt( $ch, CURLOPT_TIMEOUT, 60 ); // connect time out 60s
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'content-area: Merchant', 'version' => 3 ) );
		$result = curl_exec( $ch );
		$status = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		if ( curl_error( $ch ) ) {
			return false;
		}

		if ( $status != 200 ) {
			curl_close( $ch );

			return false;
		}
		// close curl
		curl_close( $ch );

		return $result;
	}

	public function verifyReturnUrl( $object ) {
		$secure_hash = $_GET['secure_code'];
		unset( $_GET['secure_code'] );

		$param['transaction_info']    = isset( $_GET['transaction_info'] ) ? $_GET['transaction_info'] : '';
		$param['order_code']          = isset( $_GET['order_code'] ) ? $_GET['order_code'] : '';
		$param['order_email']         = isset( $_GET['order_email'] ) ? $_GET['order_email'] : '';
		$param['order_session']       = isset( $_GET['order_session'] ) ? $_GET['order_session'] : '';
		$param['price']               = isset( $_GET['price'] ) ? $_GET['price'] : '';
		$param['site_code']           = isset( $_GET['site_code'] ) ? $_GET['site_code'] : '';
		$param['response_code']       = isset( $_GET['response_code'] ) ? $_GET['response_code'] : '';
		$param['response_message']    = isset( $_GET['response_message'] ) ? $_GET['response_message'] : '';
		$param['payment_id']          = isset( $_GET['payment_id'] ) ? $_GET['payment_id'] : '';
		$param['payment_type']        = isset( $_GET['payment_type'] ) ? $_GET['payment_type'] : '';
		$param['payment_time']        = isset( $_GET['payment_time'] ) ? $_GET['payment_time'] : '';
		$param['error_text']          = isset( $_GET['error_text'] ) ? $_GET['error_text'] : '';
		$param['payment_name']        = isset( $_GET['payment_name'] ) ? $_GET['payment_name'] : '';
		$param['payment_fullname']    = isset( $_GET['payment_fullname'] ) ? $_GET['payment_fullname'] : '';
		$param['payment_bank_code']   = isset( $_GET['payment_bank_code'] ) ? $_GET['payment_bank_code'] : '';
		$param['payment_bank_holder'] = isset( $_GET['payment_bank_holder'] ) ? $_GET['payment_bank_holder'] : '';
		$param['payment_coupon']      = isset( $_GET['payment_coupon'] ) ? $_GET['payment_coupon'] : '';
		ksort( $param );

		$md5HashData = '';
		foreach ( $param as $key => $value ) {
			if ( $key != "secure_code" && strlen( $value ) > 0 ) {
				$md5HashData .= $key . "=" . $value . "&";
			}
		}
		$md5HashData = rtrim( $md5HashData, "&" );
		if ( strtoupper( $secure_hash ) == strtoupper( hash_hmac( 'SHA256', $md5HashData, pack( 'H*', $object->wepay_secret_key ) ) ) ) {
			return true;
		} else {
			return false;
		}
	}

	public function getResponseDescriptionInternational( $responseCode ) {
		switch ( $responseCode ) {
			case "0" :
				$result = "Giao dịch thành công";
				break;
			case "?" :
				$result = "Tình trạng giao dịch không xác định";
				break;
			case "1" :
				$result = "Lỗi không xác định";
				break;
			case "2" :
				$result = "Ngân hàng từ tối giao dịch";
				break;
			case "3" :
				$result = "Không có trả lời từ Ngân hàng";
				break;
			case "4" :
				$result = "Thẻ hết hạn";
				break;
			case "5" :
				$result = "Số dư không đủ để thanh toán";
				break;
			case "6" :
				$result = "Lỗi giao tiếp với Ngân hàng";
				break;
			case "7" :
				$result = "Lỗi Hệ thống máy chủ Thanh toán";
				break;
			case "8" :
				$result = "Loại giao dịch không được hỗ trợ";
				break;
			case "9" :
				$result = "Ngân hàng từ chối giao dịch (không liên hệ với Ngân hàng)";
				break;
			case "A" :
				$result = "giao dịch Aborted";
				break;
			case "B" :
				$result = "Bị chặn do có rủi ro giả mạo";
				break;
			case "C" :
				$result = "giao dịch bị hủy bỏ";
				break;
			case "D" :
				$result = "giao dịch hoãn lại đã được nhận và đang chờ xử lý";
				break;
			case "E" :
				$result = "Referred";
				break;
			case "F" :
				$result = "3D Secure xác thực không thành công";
				break;
			case "I" :
				$result = "Card Security Code xác minh không thành công";
				break;
			case "L" :
				$result = "Mua sắm giao dịch đã bị khoá (Xin vui lòng thử lại sau giao dịch)";
				break;
			case "N" :
				$result = "Chủ thẻ không ghi danh vào chương trình xác thực";
				break;
			case "P" :
				$result = "giao dịch đã được nhận bởi các Adaptor Thanh toán và đang được xử lý";
				break;
			case "R" :
				$result = "giao dịch đã không được xử lý - Đã đạt đến giới hạn của những cố gắng thử lại cho phép";
				break;
			case "S" :
				$result = "SessionID bị trùng (OrderInfo)";
				break;
			case "T" :
				$result = "Địa chỉ xác minh không đúng";
				break;
			case "U" :
				$result = "Card Security Code không đúng";
				break;
			case "V" :
				$result = "Địa chỉ xác minh và Card Security Code không đúng";
				break;
			case "9999":
				$result = "Giao dịch có rủi ro giả mạo";
				break;
			case "9998":
				$result = "Giao dịch có rủi ro giả mạo, cần xác thực chủ thẻ";
				break;
			case "PG":
				$result = "Không tồn tại giao dịch trên hệ thống";
				break;
			default  :
				$result = "Không thể xác định";
		}

		return $result;
	}

	public function getResponseDescriptionDomestic( $responseCode ) {
		switch ( $responseCode ) {
			case "0" :
				$result = "Giao dịch thành công";
				break;
			case "1" :
				$result = "Ngân hàng từ chối giao dịch";
				break;
			case "3" :
				$result = "Mã đơn vị không tồn tại";
				break;
			case "4" :
				$result = "Không đúng access code";
				break;
			case "5" :
				$result = "Số tiền không hợp lệ";
				break;
			case "6" :
				$result = "Mã tiền tệ không tồn tại";
				break;
			case "7" :
				$result = "Lỗi không xác định";
				break;
			case "8" :
				$result = "Số thẻ không đúng";
				break;
			case "9" :
				$result = "Tên chủ thẻ không đúng";
				break;
			case "10" :
				$result = "Thẻ hết hạn/Thẻ bị khóa";
				break;
			case "11" :
				$result = "Thẻ chưa đăng ký sử dụng dịch vụ thanh toán trực tuyến.";
				break;
			case "12" :
				$result = "Ngày phát hành/Hết hạn không đúng";
				break;
			case "13" :
				$result = "Vượt quá hạn mức thanh toán";
				break;
			case "21" :
				$result = "Số dư không đủ để thanh toán";
				break;
			case "99" :
				$result = "Người sử dụng hủy giao dịch";
				break;
			case "100" :
				$result = "Không nhập thông tin thẻ/ Hủy giao dịch thanh toán";
				break;
			case "PG":
				$result = "Không tồn tại giao dịch trên hệ thống";
				break;
			default  :
				$result = "Không thể xác định";
		}

		return $result;
	}

	public function queryOrderStatus( $order_code, $api, $object, $command = 'queryStatus' ) {
		$api_url = $api . '/query';
		$params  = array(
			'site_code'  => strval( $object->wepay_site_code ),
			'order_code' => strval( $order_code ),
			'command'    => $command,
		);

		$params['secure_hash'] = $this->_createHASH( $params, $object->wepay_secret_key );
		$result                = $this->_makeRequest( $api_url, $params );
		$result                = json_decode( $result );

		return $result;
	}
}
