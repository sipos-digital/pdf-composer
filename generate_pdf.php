<?php

use Elementor\Controls_Manager;
use Elementor\Core\Admin\Admin_Notices;
use ElementorPro\Core\Utils\Hints;
use ElementorPro\Core\Utils;
use ElementorPro\Core\Utils\Collection;
use ElementorPro\Modules\Forms\Classes\Ajax_Handler;
use ElementorPro\Modules\Forms\Classes\Action_Base;
use ElementorPro\Modules\Forms\Classes\Form_Record;
use ElementorPro\Modules\Forms\Fields\Upload;
use \setasign\Fpdi\Fpdi;
class Compose_PDF extends \ElementorPro\Modules\Forms\Classes\Action_Base {

    private $files_base = [
        PDFC_PATH . 'assets/pdf/static/1.pdf',
        PDFC_PATH . 'assets/pdf/static/2.pdf',
        PDFC_PATH . 'assets/pdf/static/3.pdf',
        PDFC_PATH . 'assets/pdf/static/4.pdf',
        PDFC_PATH . 'assets/pdf/static/divider.pdf'
    ];
    private $files_outro = [
        PDFC_PATH . 'assets/pdf/static/end.pdf'
    ];

    private $pdf;

    private $score;

    public function get_name(): string {
        return 'compose_pdf';
    }

    public function get_label(): string {
        return esc_html__( 'Compose PDF', 'pdf-composer' );
    }

    public function register_settings_section( $widget ) {
        $widget->start_controls_section(
            $this->get_control_id( 'sd_pdf_section_email' ),
            [
                'label' => $this->get_label(),
                'tab' => Controls_Manager::TAB_CONTENT,
                'condition' => [
                    'submit_actions' => $this->get_name(),
                ],
            ]
        );

        $this->maybe_add_site_mailer_notice( $widget );

        $widget->add_control(
            $this->get_control_id( 'sd_pdf_email_to' ),
            [
                'label' => esc_html__( 'To', 'elementor-pro' ),
                'type' => Controls_Manager::TEXT,
                'default' => get_option( 'admin_email' ),
                'ai' => [
                    'active' => false,
                ],
                'placeholder' => get_option( 'admin_email' ),
                'label_block' => true,
                'title' => esc_html__( 'Separate emails with commas', 'elementor-pro' ),
                'render_type' => 'none',
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        /* translators: %s: Site title. */
        $default_message = sprintf( __( 'New message from "%s"', 'elementor-pro' ), get_option( 'blogname' ) );

        $widget->add_control(
            $this->get_control_id( 'sd_pdf_email_subject' ),
            [
                'label' => esc_html__( 'Subject', 'elementor-pro' ),
                'type' => Controls_Manager::TEXT,
                'default' => $default_message,
                'ai' => [
                    'active' => false,
                ],
                'placeholder' => $default_message,
                'label_block' => true,
                'render_type' => 'none',
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $widget->add_control(
            $this->get_control_id( 'sd_pdf_email_content' ),
            [
                'label' => esc_html__( 'Message', 'elementor-pro' ),
                'type' => Controls_Manager::TEXTAREA,
                'default' => '[all-fields]',
                'ai' => [
                    'active' => false,
                ],
                'placeholder' => '[all-fields]',
                'description' => sprintf(
                /* translators: %s: The [all-fields] shortcode. */
                    esc_html__( 'By default, all form fields are sent via %s shortcode. To customize sent fields, copy the shortcode that appears inside each field and paste it above.', 'elementor-pro' ),
                    '<code>[all-fields]</code>'
                ),
                'render_type' => 'none',
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $site_domain = Utils::get_site_domain();

        $widget->add_control(
            $this->get_control_id( 'sd_pdf_email_from' ),
            [
                'label' => esc_html__( 'From Email', 'elementor-pro' ),
                'type' => Controls_Manager::TEXT,
                'default' => 'email@' . $site_domain,
                'ai' => [
                    'active' => false,
                ],
                'render_type' => 'none',
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $widget->add_control(
            $this->get_control_id( 'sd_pdf_email_from_name' ),
            [
                'label' => esc_html__( 'From Name', 'elementor-pro' ),
                'type' => Controls_Manager::TEXT,
                'default' => get_bloginfo( 'name' ),
                'ai' => [
                    'active' => false,
                ],
                'render_type' => 'none',
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $widget->add_control(
            $this->get_control_id( 'sd_pdf_email_reply_to' ),
            [
                'label' => esc_html__( 'Reply-To', 'elementor-pro' ),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    '' => '',
                ],
                'render_type' => 'none',
            ]
        );

        $widget->add_control(
            $this->get_control_id( 'sd_pdf_email_to_cc' ),
            [
                'label' => esc_html__( 'Cc', 'elementor-pro' ),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'ai' => [
                    'active' => false,
                ],
                'title' => esc_html__( 'Separate emails with commas', 'elementor-pro' ),
                'render_type' => 'none',
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $widget->add_control(
            $this->get_control_id( 'sd_pdf_email_to_bcc' ),
            [
                'label' => esc_html__( 'Bcc', 'elementor-pro' ),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'ai' => [
                    'active' => false,
                ],
                'title' => esc_html__( 'Separate emails with commas', 'elementor-pro' ),
                'render_type' => 'none',
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $widget->add_control(
            $this->get_control_id( 'sd_pdf_form_metadata' ),
            [
                'label' => esc_html__( 'Meta Data', 'elementor-pro' ),
                'type' => Controls_Manager::SELECT2,
                'multiple' => true,
                'label_block' => true,
                'separator' => 'before',
                'default' => [
                    'date',
                    'time',
                    'page_url',
                    'user_agent',
                    'remote_ip',
                    'credit',
                ],
                'options' => [
                    'date' => esc_html__( 'Date', 'elementor-pro' ),
                    'time' => esc_html__( 'Time', 'elementor-pro' ),
                    'page_url' => esc_html__( 'Page URL', 'elementor-pro' ),
                    'user_agent' => esc_html__( 'User Agent', 'elementor-pro' ),
                    'remote_ip' => esc_html__( 'Remote IP', 'elementor-pro' ),
                    'credit' => esc_html__( 'Credit', 'elementor-pro' ),
                ],
                'render_type' => 'none',
            ]
        );

        $widget->add_control(
            $this->get_control_id( 'sd_pdf_email_content_type' ),
            [
                'label' => esc_html__( 'Send As', 'elementor-pro' ),
                'type' => Controls_Manager::SELECT,
                'default' => 'html',
                'render_type' => 'none',
                'options' => [
                    'html' => esc_html__( 'HTML', 'elementor-pro' ),
                    'plain' => esc_html__( 'Plain', 'elementor-pro' ),
                ],
            ]
        );

        $widget->end_controls_section();
    }

    public function maybe_add_site_mailer_notice( $widget ) {
        $notice_id = 'site_mailer_forms_email_notice';

        if ( ! Hints::should_show_hint( $notice_id ) ) {
            return;
        }

        $plugin_slug = 'site-mailer';

        $one_subscription = method_exists( Hints::class, 'is_plugin_connected_to_one_subscription' ) && Hints::is_plugin_connected_to_one_subscription();
        $is_installed = Hints::is_plugin_installed( $plugin_slug );
        $is_active = Hints::is_plugin_active( $plugin_slug );

        if ( $is_active ) {
            return;
        }

        if ( $one_subscription ) {
            if ( ! $is_installed ) {
                $notice_content = esc_html__( 'Make sure your site’s emails reach the inbox every time. Site Mailer is included in your ONE subscription.', 'elementor-pro' );
                $button_text = esc_html__( 'Install now', 'elementor-pro' );
                $button_url = Hints::get_plugin_install_url( $plugin_slug );
                $campaign_data = [
                    'name' => 'site_mailer_forms_email_notice',
                    'campaign' => 'sm-plg-form-v1-one-install',
                    'source' => 'sm-editor-form-one-install',
                    'medium' => 'wp-dash-one',
                ];
            } elseif ( ! $is_active ) {
                $notice_content = esc_html__( 'Ensure your site’s emails reach the inbox every time. Site Mailer is included in your ONE subscription. Activate it to continue.', 'elementor-pro' );
                $button_text = esc_html__( 'Activate now', 'elementor-pro' );
                $button_url = Hints::get_plugin_activate_url( $plugin_slug );
                $campaign_data = [
                    'name' => 'site_mailer_forms_email_notice',
                    'campaign' => 'sm-plg-form-v1-one-activate',
                    'source' => 'sm-editor-form-one-activate',
                    'medium' => 'wp-dash-one',
                ];
            }
        } else {
            $notice_content = esc_html__( 'Experiencing email deliverability issues? Get your emails delivered with Site Mailer.', 'elementor-pro' );

            if ( 2 === Utils\Abtest::get_variation( 'plg_site_mailer_submission' ) ) {
                $notice_content = esc_html__( 'Make sure your emails reach the inbox every time with Site Mailer.', 'elementor-pro' );
            }

            if ( ! $is_installed ) {
                $button_text = esc_html__( 'Install now', 'elementor-pro' );
                $button_url = Hints::get_plugin_install_url( $plugin_slug );
                $campaign_data = [
                    'name' => 'site_mailer_forms_email_notice',
                    'campaign' => 'sm-form-install',
                    'source' => 'sm-plg-form-v1-install',
                    'medium' => 'wp-dash',
                ];
            } elseif ( ! $is_active ) {
                $button_text = esc_html__( 'Activate now', 'elementor-pro' );
                $button_url = Hints::get_plugin_activate_url( $plugin_slug );
                $campaign_data = [
                    'name' => 'site_mailer_forms_email_notice',
                    'campaign' => 'sm-form-activate',
                    'source' => 'sm-plg-form-v1-activate',
                    'medium' => 'wp-dash',
                ];
            }
        }

        $widget->add_control(
            $this->get_control_id( 'sd_pdf_site_mailer_promo' ),
            [
                'type' => Controls_Manager::RAW_HTML,
                'raw' => Hints::get_notice_template( [
                    'display' => ! Hints::is_dismissed( $notice_id ),
                    'type' => 'info',
                    'content' => $notice_content,
                    'icon' => true,
                    'dismissible' => $notice_id,
                    'button_text' => $button_text,
                    'button_event' => $notice_id,
                    'button_data' => [
                        'action_url' => $button_url,
                        'source' => $campaign_data['source'],
                    ],
                ], true ),
            ]
        );
    }

    /**
     * @throws \setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException
     * @throws \setasign\Fpdi\PdfReader\PdfReaderException
     * @throws \setasign\Fpdi\PdfParser\PdfParserException
     * @throws \setasign\Fpdi\PdfParser\Filter\FilterException
     * @throws \setasign\Fpdi\PdfParser\Type\PdfTypeException
     * @throws Exception
     */
    public function run($record, $ajax_handler ): void {

        $score = $record->get_field(['id' => 'lead_score']);
        $this->score = $score['lead_score']['value'];

        // Define File Name
        $file_name = wp_date('Y_m_d_His') . '_zhrnutie.pdf';

        // Prepare dynamic files from answers
        $dynamic_files = $this->prepare_files($record->get_formatted_data());

        // PDF Structure
        $files = array_merge(
            $this->files_base,
            $dynamic_files,
            $this->files_outro
        );

        // Check if files exist before composing the PDF
        $files = $this->validate_files($files);

        $this->pdf = $this->create_pdf($file_name, $files);

        $this->email($record, $ajax_handler);

    }

    /**
     * Email Function - duplicate from EMAIL Action
     * @tested up to 'ELEMENTOR_PRO_VERSION', '3.35.1'
     * @param $record
     * @param $ajax_handler
     * @return void
     * @throws Exception
     */
    private function email($record, $ajax_handler): void {
        $settings = $record->get( 'form_settings' );
        $send_html = 'plain' !== $settings[ $this->get_control_id( 'email_content_type' ) ];
        $line_break = $send_html ? '<br>' : "\n";

        $fields = [
            'email_to' => get_option( 'admin_email' ),
            /* translators: %s: Site title. */
            'email_subject' => sprintf( esc_html__( 'New message from "%s"', 'elementor-pro' ), get_bloginfo( 'name' ) ),
            'email_content' => '[all-fields]',
            'email_from_name' => get_bloginfo( 'name' ),
            'email_from' => get_bloginfo( 'admin_email' ),
            'email_reply_to' => 'julius@sipos.digital',
            'email_to_cc' => '',
            'email_to_bcc' => '',
        ];

        foreach ( $fields as $key => $default ) {
            $setting = trim( $settings[ $this->get_control_id( $key ) ] );
            $setting = $record->replace_setting_shortcodes( $setting );
            if ( ! empty( $setting ) ) {
                $normalized_key = str_replace('sd_pdf_', '', $key);
                $fields[ $normalized_key ] = $setting;
            }
        }

        $email_reply_to = $this->get_reply_to( $record, $fields );

        $fields['email_content'] = $this->replace_content_shortcodes( $fields['email_content'], $record, $line_break );

        $email_meta = '';

        $form_metadata_settings = $settings[ $this->get_control_id( 'form_metadata' ) ];

        foreach ( $record->get( 'meta' ) as $id => $field ) {
            if ( in_array( $id, $form_metadata_settings ) ) {
                $email_meta .= $this->field_formatted( $field ) . $line_break;
            }
        }

        if ( ! empty( $email_meta ) ) {
            $fields['email_content'] .= $line_break . '---' . $line_break . $line_break . $email_meta;
        }

        $headers = sprintf( 'From: %s <%s>' . "\r\n", $fields['email_from_name'], $fields['email_from'] );
        $headers .= sprintf( 'Reply-To: %s' . "\r\n", $email_reply_to );

        if ( $send_html ) {
            $headers .= 'Content-Type: text/html; charset=UTF-8' . "\r\n";
        }

        $cc_header = '';
        if ( ! empty( $fields['email_to_cc'] ) ) {
            $cc_header = 'Cc: ' . $fields['email_to_cc'] . "\r\n";
        }

        /**
         * Email headers.
         *
         * Filters the headers sent when an email is sent from Elementor forms. This
         * hook allows developers to alter email headers triggered by Elementor forms.
         *
         * @since 1.0.0
         *
         * @param string|array $headers Additional headers.
         */
        $headers = apply_filters( 'elementor_pro/forms/wp_mail_headers', $headers );

        /**
         * Email content.
         *
         * Filters the content of the email sent by Elementor forms. This hook allows
         * developers to alter the content of the email sent by Elementor forms.
         *
         * @since 1.0.0
         *
         * @param string $email_content Email content.
         */
        $fields['email_content'] = apply_filters( 'elementor_pro/forms/wp_mail_message', $fields['email_content'] );


        $attachments[] = $this->pdf;
        $email_sent = wp_mail(
            $fields['email_to'],
            $fields['email_subject'],
            $fields['email_content'],
            $headers . $cc_header, $attachments );

        if ( ! empty( $fields['email_to_bcc'] ) ) {
            $bcc_emails = explode( ',', $fields['email_to_bcc'] );
            foreach ( $bcc_emails as $bcc_email ) {
                wp_mail(
                    trim( $bcc_email ),
                    $fields['email_subject'],
                    $fields['email_content'],
                    $headers,
                    $attachments
                );
            }
        }

        foreach ( $attachments as $file ) {
            @unlink( $file );
        }

        /**
         * Elementor form mail sent.
         *
         * Fires when an email was sent successfully by Elementor forms. This
         * hook allows developers to add functionality after mail sending.
         *
         * @since 1.0.0
         *
         * @param array       $settings Form settings.
         * @param Form_Record $record   An instance of the form record.
         */
        do_action( 'elementor_pro/forms/mail_sent', $settings, $record );

        if ( ! $email_sent ) {
            $message = Ajax_Handler::get_default_message( Ajax_Handler::SERVER_ERROR, $settings );

            $ajax_handler->add_error_message( $message );

            throw new \Exception( $message );
        }
    }

    /**
     * Compose PDF file, if the process fails, return false.
     *
     * @param $filename
     * @param $files
     * @throws \setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException
     * @throws \setasign\Fpdi\PdfParser\Filter\FilterException
     * @throws \setasign\Fpdi\PdfParser\PdfParserException
     * @throws \setasign\Fpdi\PdfParser\Type\PdfTypeException
     * @throws \setasign\Fpdi\PdfReader\PdfReaderException
     */
    private function create_pdf($filename, $files) {
        // Initiate PDF Class
        $pdf = new \setasign\Fpdi\Fpdi();

        // Compose the file
        foreach ($files as $file) {
            $pageCount = $pdf->setSourceFile($file);
            for ($i = 1; $i <= $pageCount; $i++) {
                $tpl = $pdf->importPage($i);
                $pdf->AddPage('L', [297, 210]);
                $pdf->useTemplate($tpl, 0, 0, 297, 210);
            }
        }

        // Define File Path
        $upload_dir = wp_upload_dir();
        $pdf_path = $upload_dir['basedir'] . '/' . $filename;

        // Output the PDF into one file /wp-content/$file_name
        $pdf->Output('F', $pdf_path);

        if (file_exists($pdf_path)) {
            return $pdf_path;
        }

        return false;
    }

    /**
     * Parse the keys from the answers and put them into
     * PDF names with corresponding file paths
     *
     * @param array $data
     * @return array
     */
    private function prepare_files(array $data): array {
        $data = array_values($data);
        $score = $this->score;

        $dynamic_files = [];
        $i = 0;

        foreach ($data as $answer) {
            if (!str_contains($answer, '#')) {
                continue; // vynechá stringy bez #
            }

            $clean = strstr($answer, '#', true) ?: $answer;
            $dynamic_files[$i] = PDFC_PATH . 'assets/pdf/dynamic/' . $clean . '.pdf';
            $i++;
        }

        if ($score >= 10 && $score <= 12) {
            $dynamic_files[$i] = PDFC_PATH . 'assets/pdf/dynamic/10_12.pdf';
        } elseif ($score >= 13 && $score <= 15) {
            $dynamic_files[$i] = PDFC_PATH . 'assets/pdf/dynamic/13_15.pdf';
        } elseif ($score >= 16 && $score <= 17) {
            $dynamic_files[$i] = PDFC_PATH . 'assets/pdf/dynamic/16_17.pdf';
        } elseif ($score === 18) {
            $dynamic_files[$i] = PDFC_PATH . 'assets/pdf/dynamic/18.pdf';
        }
        return $dynamic_files;

    }

    /**
     * Validate File Paths - Does the files exist?
     * If not = delete the array entry
     *
     * @param array $paths
     * @return array
     */
    private function validate_files(array $paths): array {
        return array_values(array_filter(
            $paths,
            fn($path) => is_string($path) && file_exists($path)
        ));
    }

    public function on_export( $element ) {
        $controls_to_unset = [
            'sd_pdf_email_to',
            'sd_pdf_email_from',
            'sd_pdf_email_from_name',
            'sd_pdf_email_subject',
            'sd_pdf_email_reply_to',
            'sd_pdf_email_to_cc',
            'sd_pdf_email_to_bcc',
        ];

        foreach ( $controls_to_unset as $base_id ) {
            $control_id = $this->get_control_id( $base_id );
            unset( $element['settings'][ $control_id ] );
        }

        return $element;
    }

    private function field_formatted( $field ) {
        $formatted = '';
        if ( ! empty( $field['title'] ) ) {
            $formatted = sprintf( '%s: %s', $field['title'], $field['value'] );
        } elseif ( ! empty( $field['value'] ) ) {
            $formatted = sprintf( '%s', $field['value'] );
        }

        return $formatted;
    }

    // Allow overwrite the control_id with a prefix, @see Email2
    protected function get_control_id( $control_id ) {
        return $control_id;
    }

    protected function get_reply_to( $record, $fields ) {
        $email_reply_to  = 'julius@sipos.digital';
        return $email_reply_to;
    }

    /**
     * @param string      $email_content
     * @param Form_Record $record
     *
     * @return string
     */
    private function replace_content_shortcodes( $email_content, $record, $line_break ): string
    {
        $email_content = do_shortcode( $email_content );
        $all_fields_shortcode = '[all-fields]';

        if ( false !== strpos( $email_content, $all_fields_shortcode ) ) {
            $text = '';
            foreach ( $record->get( 'fields' ) as $field ) {

                $formatted = $this->field_formatted( $field );
                if ( ( 'textarea' === $field['type'] ) && ( '<br>' === $line_break ) ) {
                    $formatted = str_replace( [ "\r\n", "\n", "\r" ], '<br />', $formatted );
                }

                $text .= $formatted . $line_break;
            }

            $email_content = str_replace( $all_fields_shortcode, $text, $email_content );

        }

        return $email_content;
    }


}