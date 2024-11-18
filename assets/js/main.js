jQuery(document).ready(function ($){


    // Visibility state of the shipping address fields
    let shipping_address_state = false;

    // Get the shipping method ID selected in plugin settings for DPD pickup (provided by server in the template)
    let shipping_method_id = $('#dpd-pickup-data').data('shipping-method');
    //console.log('Shipping method id from server: ' + shipping_method_id);

    // Get the selected shipping method during page load.
    // If the selected method matches the pickup shipping method ID, hide the shipping address fields, including wrapper with checkbox
    let initial_instance_value = $('#shipping_method input[type="radio"]:checked').val();
    let initial_instance_id = initial_instance_value.split(':')[1];
    //console.log('Shipping method id on load: ' + initial_instance_id);
    if(initial_instance_id == shipping_method_id){
        $('.woocommerce-shipping-fields').css('display', 'none');
    }

    // get current visibility state of the shipping address fields
    shipping_address_state = $('.shipping_address').css('display') === 'none';
    //console.log('Shipping address state init: ' + shipping_address_state);


    // change shipping address fields visibility state on click
    $(document).on('click', '.woocommerce-shipping-fields', function (){
        shipping_address_state = $('.shipping_address').css('display') === 'none';
        //console.log('Changed shipping address state: ' + shipping_address_state);
    })



    // Activate the DPD pickup point widget when the link is clicked
    $(document).on('click', '#wc-rw-dpd-pickup-select-point-link',function (e){
        e.preventDefault();
        if (!$('#full-screen-iframe').length) {
            $('body').append(`
                <div id="wc-rw-dpd-pickup-full-screen-iframe" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: white; z-index: 9999;">
                    <iframe src="https://api.dpd.cz/widget/latest/index.html" style="width: 100%; height: 100%; border: none;"></iframe>
                </div>
            `);
        }
    })

    // Listen for "widgetClose" message from the DPD widget and remove the iframe when the close event is triggered
    window.addEventListener("message", (event) => {
        if(event.data.dpdWidget && event.data.dpdWidget.message === "widgetClose") {
            //console.log("DPD pickup point widget is closed")
            $('#wc-rw-dpd-pickup-full-screen-iframe').remove();
        }
    }, false);


    /**
     * Event listener for the "message" event to handle data sent from the DPD widget.
     *
     * - When a message is received from the widget (validated by `event.data.dpdWidget`),
     *   this function attempts to save the selected pickup point using an AJAX call.
     * - If the save is successful, the UI is updated with the selected pickup point details.
     * - If the save fails, an error message is displayed to the user.
     *
     * @param {MessageEvent} event - The message event containing data from the DPD widget.
     */
    window.addEventListener("message", async (event) => {
        if (event.data.dpdWidget && !event.data.dpdWidget.message) {
            $('#wc-rw-dpd-pickup-full-screen-iframe').remove();
            try {
                start_wc_spinner('woocommerce-shipping-methods');
                let pickupPoint = event.data.dpdWidget
                //console.log(event.data.dpdWidget);
                let set_pickup_point_result = await set_pickup_point_data(pickupPoint); //error handling before insert data by js + spinner loader
                if(!set_pickup_point_result){
                    throw new Error(wc_rw_dpd_pickup_ajax_obj.pickup_point_save_error);
                }
                $('#wc-rw-dpd-pickup-select-point-link-wrapper').empty()
                    .append(
                        `<div class="wc-rw-dpd-pickup-point-info">
                            <span>${pickupPoint.contactInfo.name}</span></br>
                            <span>${pickupPoint.pickupPointResult}</span></br>
                            <a class='wc-rw-dpd-pickup-select-point-link' id='wc-rw-dpd-pickup-select-point-link'>${wc_rw_dpd_pickup_ajax_obj.change_pickup_point}</a>
                        </div>`
                    );
            } catch (error) {
                alert(error.message);
            } finally {
                stop_wc_spinner('woocommerce-shipping-methods');
            }
        }
    }, false);


    /**
     * Toggles the visibility of shipping address fields based on the selected shipping method.
     *
     * - Listens for changes in the selected shipping method (radio input) within the order review section.
     * - Hides or shows the shipping address fields depending on whether the selected shipping method matches a predefined ID.
     * - Dynamically adjusts the appearance state of `.shipping_address` and `.woocommerce-shipping-fields` elements.
     */
    $('#order_review').on('change', '#shipping_method input[type="radio"]', function () {
        let value = $(this).val();
        let instance_id = value.split(':')[1];
        //console.log(instance_id);
         if(instance_id == shipping_method_id) {
             $('.shipping_address').css('display', 'none');
             $('.woocommerce-shipping-fields').css('display', 'none');
         }else {
             $('.woocommerce-shipping-fields').css('display', 'block');
             if(shipping_address_state){
                 $('.shipping_address').css('display', 'block');
             }
         }
    });

    /**
     * Starts or stops a WooCommerce-style spinner on a specific element.
     *
     * @param {string} class_name - The class name of the element to be blocked or unblocked.
     */
    function start_wc_spinner(class_name){
       let element = $('.' + class_name);
       element.block({
           message: null,
           overlayCSS: {
               background: '#fff',
               opacity: 0.6
           }
       })

    }

    /**
     * Stops or stops a WooCommerce-style spinner on a specific element.
     *
     * @param {string} class_name - The class name of the element to be blocked or unblocked.
     */
    function stop_wc_spinner(class_name){
        let element = $('.' + class_name);
        element.unblock();
    }






    /**
     * Sends pickup point data to the server using an AJAX request.
     *
     * - Resolves to `true` if the server successfully processes the request.
     * - Resolves to `false` if the server returns an error or the connection fails.
     *
     * @param {Object} data - The data object to be sent to the server.
     * @returns {Promise<boolean>} - A Promise that resolves to `true` (success) or `false` (failure).
     */
    function set_pickup_point_data(data) {

        return new Promise((resolve,reject)=>{

            $.ajax({
                type: "POST",
                url: wc_rw_dpd_pickup_ajax_obj.ajax_url,
                data: {
                    action: "wc_rw_set_pickup_point_data_action",
                    pickup_point_data: data,
                    security: wc_rw_dpd_pickup_ajax_obj.security
                },
                dataType: "json",
                encode: true
            })
                .done((response) => {
                    if(!response.success){
                        console.error(response.data || "Unknown error");
                    }
                    resolve(response.success);
                })
                .fail(() => {
                    console.error('Server connection error.')
                    resolve(false);
                })
        })

    }
})

