<?php

/**
 * Monime Blocks Support
 *
 * @package Monime_Checkout
 */

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

defined('ABSPATH') || exit;

/**
 * WC_Monime_Blocks_Support class.
 */
final class WC_Monime_Blocks_Support extends AbstractPaymentMethodType
{
	/** Payment method ID used by WooCommerce Blocks. */
	protected $name = 'monime';

	/** @var WcMonimeGateway */
	private $gateway;

	/**
	 * Load saved gateway settings and resolve the matching classic gateway.
	 */
	public function initialize()
	{
		$this->settings = get_option('woocommerce_monime_settings', []);

		// Get gateway instance
		if (class_exists('WcMonimeGateway')) {
			$gateways = WC()->payment_gateways->get_available_payment_gateways();
			if (isset($gateways['monime'])) {
				$this->gateway = $gateways['monime'];
			}
		}

		add_filter('woocommerce_shared_settings', [$this, 'add_payment_method_data_to_settings'], 10, 1);
	}

	/**
	 * Add Monime metadata to WooCommerce shared settings for checkout scripts.
	 */
	public function add_payment_method_data_to_settings($settings)
	{
		if (!isset($settings['monime_data'])) {
			$settings['monime_data'] = [
				'title'       => $this->get_setting('title', 'Monime Checkout'),
				'description' => $this->get_setting('description', 'Pay securely with Cards, Mobile Money, Bank Transfer, or Digital Wallet via Monime.'),
				'supports'    => $this->get_supported_features(),
			];
		}
		return $settings;
	}

	/**
	 * Determine whether Monime should be shown in Blocks checkout.
	 */
	public function is_active()
	{
		if (empty($this->settings)) {
			$this->initialize();
		}

		$enabled = !empty($this->settings['enabled']) && 'yes' === $this->settings['enabled'];
		if (!$enabled) {
			return false;
		}

		return $this->gateway ? $this->gateway->is_available() : true;
	}

	/**
	 * Register the inline Blocks integration script and return its handle.
	 */
	public function get_payment_method_script_handles()
	{
		wp_register_script(
			'wc-monime-blocks-integration',
			false,
			['react-jsx-runtime', 'wc-blocks-registry', 'wc-sanitize', 'wc-settings', 'wp-element', 'wp-html-entities', 'wp-i18n', 'wp-polyfill'],
			'2.0.1',
			true
		);

		$inline_script = $this->get_inline_script();
		wp_add_inline_script('wc-monime-blocks-integration', $inline_script, 'after');

		return ['wc-monime-blocks-integration'];
	}

	/**
	 * Build the JavaScript that registers Monime as a Blocks payment method.
	 *
	 * The data injected into the script comes from get_payment_method_data().
	 */
	private function get_inline_script()
	{
		$payment_method_data = $this->get_payment_method_data();
		$data_json = wp_json_encode($payment_method_data);

		$script = '(function(){"use strict";var registry=window.wc&&window.wc.wcBlocksRegistry,settings=window.wc&&window.wc.wcSettings,i18n=window.wp.i18n,htmlEntities=window.wp.htmlEntities,sanitize=window.wc&&window.wc.sanitize,element=window.wp.element;if(!registry||!settings||!element){console.warn("Monime: Required WooCommerce Blocks dependencies not found");return;}var createElement=element.createElement,RawHTML=element.RawHTML,data=settings.getPaymentMethodData("monime",' . $data_json . ')||{},title=(0,i18n.__)("Monime WooCommerce","monime-woocommerce"),decodedTitle=(0,htmlEntities.decodeEntities)(data.title||"")||title,iconUrl=data.icon||"",providers=data.providers||{},displayBadges=providers.display||[],overflowBadges=providers.overflow||[],labelStyle={display:"flex",alignItems:"center",gap:"8px"},iconStyle={width:"20px",height:"20px"},badgeWrapStyle={display:"flex",flexWrap:"wrap",gap:"8px",margin:"8px 0",alignItems:"center"},badgeStyle={background:"#fff",border:"1px solid #e2e8f0",borderRadius:"8px",padding:"6px",display:"flex",alignItems:"center",justifyContent:"center"},badgeImgStyle={width:"36px",height:"36px",display:"block"},securityStyle={fontSize:"13px",color:"#475467",marginTop:"8px"},overflowContainerStyle={position:"relative",display:"inline-block"},overflowTriggerStyle={background:"#fff",border:"1px solid #e2e8f0",borderRadius:"8px",padding:"6px 10px",fontWeight:"600",fontSize:"13px",cursor:"pointer",display:"inline-block",minWidth:"40px",textAlign:"center"},overflowPopoverStyle={position:"absolute",bottom:"100%",left:"50%",transform:"translateX(-50%)",marginBottom:"8px",background:"#111827",color:"#fff",padding:"12px",borderRadius:"8px",display:"grid",gridTemplateColumns:"repeat(4,36px)",gap:"8px",boxShadow:"0 8px 20px rgba(15,23,42,0.35)",opacity:0,visibility:"hidden",pointerEvents:"none",transition:"opacity 0.2s ease, visibility 0.2s ease",zIndex:1000},overflowPopoverImgStyle={width:"36px",height:"36px",borderRadius:"6px",background:"#fff",padding:"4px",display:"block"};var getDescriptionNode=function(){var desc=data.description||"";if(RawHTML&&sanitize&&sanitize.sanitizeHTML){return createElement(RawHTML,{children:sanitize.sanitizeHTML(desc)});}return createElement("p",{},desc);};var ProviderBadges=function(){if(!displayBadges.length&&!overflowBadges.length){return null;}var nodes=displayBadges.map(function(badge){return createElement("span",{style:badgeStyle,key:badge.id},createElement("img",{src:badge.icon,alt:badge.label,style:badgeImgStyle}));});nodes=nodes.concat(overflowBadges.map(function(group,index){var popoverImages=(group.items||[]).map(function(item,idx){return createElement("img",{key:item.id||index+"-"+idx,src:item.icon,alt:item.label,style:overflowPopoverImgStyle});});var popover=createElement("div",{style:overflowPopoverStyle,className:"monime-overflow-popover"},popoverImages);return createElement("span",{style:overflowContainerStyle,className:"monime-overflow-container",key:"overflow-"+index},createElement("span",{style:overflowTriggerStyle},"+"+group.count),popover);}));return createElement("div",{style:badgeWrapStyle},nodes);};var Content=function(){return createElement("div",{},getDescriptionNode(),createElement(ProviderBadges,null),createElement("p",{style:securityStyle},(0,i18n.__)("You will be redirected to Monime to complete payment via HTTPS. Once done, we will bring you back automatically.","monime-woocommerce")));};var Label=function(props){var textNode=decodedTitle;if(props&&props.components&&props.components.PaymentMethodLabel){textNode=createElement(props.components.PaymentMethodLabel,{text:decodedTitle});}else{textNode=createElement("span",{},decodedTitle);}if(!iconUrl){return textNode;}return createElement("span",{style:labelStyle},createElement("img",{src:iconUrl,alt:"Monime",style:iconStyle}),textNode);};var renderedLabel=createElement(Label,{}),renderedContent=createElement(Content,{});var paymentMethodConfig={name:"monime",label:renderedLabel,content:renderedContent,edit:renderedContent,canMakePayment:function(){return!0;},ariaLabel:decodedTitle,supports:{features:data.supports||[]}};registry.registerPaymentMethod(paymentMethodConfig);var style=document.createElement("style");style.innerHTML=".monime-overflow-container:hover .monime-overflow-popover{opacity:1 !important;visibility:visible !important;pointer-events:auto !important;}";document.head.appendChild(style);})();';

		return $script;
	}

	/**
	 * Provide Monime title, description, icon, provider badges, and features.
	 */
	public function get_payment_method_data()
	{
		return [
			'title'       => $this->get_setting('title', 'Monime WooCommerce'),
			'description' => $this->get_setting('description', 'Hosted Monime checkout with Cards, Mobile Money, Bank Transfer, or Digital Wallet.'),
			'supports'    => $this->get_supported_features(),
			'icon'        => $this->gateway ? $this->gateway->icon : '',
			'providers'   => $this->gateway ? $this->gateway->get_provider_badge_data() : [
				'by_channel' => [],
				'display'    => [],
				'overflow'   => [],
				'flat'       => [],
			],
		];
	}

	/**
	 * Return WooCommerce feature support advertised by the classic gateway.
	 */
	public function get_supported_features()
	{
		return $this->gateway ? array_filter($this->gateway->supports, [$this->gateway, 'supports']) : [];
	}

	/**
	 * Read a saved gateway setting with a default fallback.
	 */
	protected function get_setting($key, $default = '')
	{
		return $this->settings[$key] ?? $default;
	}
}
