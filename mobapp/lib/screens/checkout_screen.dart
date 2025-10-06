import 'package:flutter/material.dart';
import '../extensions/app_button.dart';
import '../extensions/app_text_field.dart';
import '../extensions/colors.dart';
import '../extensions/decorations.dart';
import '../extensions/extension_util/context_extensions.dart';
import '../extensions/extension_util/int_extensions.dart';
import '../extensions/extension_util/string_extensions.dart';
import '../extensions/extension_util/widget_extensions.dart';
import '../extensions/loader_widget.dart';
import '../extensions/text_styles.dart';
import '../extensions/widgets.dart';
import '../main.dart';
import '../models/cart_response.dart';
import '../models/order_response.dart';
import '../network/rest_api.dart';
import '../utils/app_colors.dart';
import '../utils/app_common.dart';

class CheckoutScreen extends StatefulWidget {
  final CartResponse cartResponse;

  const CheckoutScreen({super.key, required this.cartResponse});

  @override
  State<CheckoutScreen> createState() => _CheckoutScreenState();
}

class _CheckoutScreenState extends State<CheckoutScreen> {
  final GlobalKey<FormState> _formKey = GlobalKey<FormState>();

  late TextEditingController _nameController;
  late TextEditingController _phoneController;
  late TextEditingController _addressController;
  late TextEditingController _noteController;

  bool _isPlacingOrder = false;

  @override
  void initState() {
    super.initState();
    _nameController = TextEditingController(text: _resolveDefaultName());
    _phoneController = TextEditingController(text: userStore.phoneNo.validate());
    _addressController = TextEditingController();
    _noteController = TextEditingController();
  }

  String _resolveDefaultName() {
    if (userStore.displayName.validate().isNotEmpty) {
      return userStore.displayName.validate();
    }

    final first = userStore.fName.validate();
    final last = userStore.lName.validate();
    return ('$first $last').trim();
  }

  @override
  void dispose() {
    _nameController.dispose();
    _phoneController.dispose();
    _addressController.dispose();
    _noteController.dispose();
    super.dispose();
  }

  Future<void> _placeOrder() async {
    if (!_formKey.currentState.validate()) {
      return;
    }

    FocusScope.of(context).unfocus();

    setState(() {
      _isPlacingOrder = true;
    });

    final request = {
      'full_name': _nameController.text.trim(),
      'phone': _phoneController.text.trim(),
      'address': _addressController.text.trim(),
      'note': _noteController.text.trim(),
    };

    await checkoutApi(request).then((CheckoutResponse value) {
      toast(value.message ?? languages.lblOrderPlacedSuccess);
      cartCountNotifier.value = 0;
      if (mounted) Navigator.pop(context, true);
    }).catchError((e) {
      toast(e.toString());
    }).whenComplete(() {
      if (mounted) {
        setState(() {
          _isPlacingOrder = false;
        });
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    final cartItems = widget.cartResponse.data ?? [];
    final summary = widget.cartResponse.summary;

    return Scaffold(
      appBar: appBarWidget(languages.lblCheckout,
          context: context, titleSpacing: 0, showBack: true),
      body: Stack(
        children: [
          SingleChildScrollView(
            padding: EdgeInsets.only(left: 16, right: 16, bottom: 24),
            child: Form(
              key: _formKey,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  16.height,
                  Text(languages.lblOrderSummary, style: boldTextStyle(size: 18)),
                  12.height,
                  Container(
                    decoration: boxDecorationWithRoundedCorners(
                      borderRadius: radius(14),
                      backgroundColor:
                          appStore.isDarkMode ? cardDarkColor : context.cardColor,
                    ),
                    padding: EdgeInsets.all(16),
                    child: Column(
                      children: [
                        ...cartItems.map((item) => _CheckoutItemTile(item: item))
                            .toList(),
                        Divider(height: 24),
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text(languages.lblOrderTotal,
                                style: secondaryTextStyle()),
                            Text(
                              '${userStore.currencySymbol.validate()}${(summary?.totalAmount ?? 0).toStringAsFixed(2)}',
                              style: boldTextStyle(size: 18, color: primaryColor),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                  24.height,
                  Text(languages.lblShippingAddress, style: boldTextStyle(size: 18)),
                  12.height,
                  AppTextField(
                    controller: _nameController,
                    textFieldType: TextFieldType.NAME,
                    decoration: inputDecoration(context,
                        hint: languages.lblFullName, labelText: languages.lblFullName),
                    validator: (value) {
                      if (value.validate().isEmpty) {
                        return languages.lblEnterFirstName;
                      }
                      return null;
                    },
                  ),
                  16.height,
                  AppTextField(
                    controller: _phoneController,
                    textFieldType: TextFieldType.PHONE,
                    decoration: inputDecoration(context,
                        hint: languages.lblPhoneNumber, labelText: languages.lblPhoneNumber),
                    validator: (value) {
                      if (value.validate().isEmpty) {
                        return languages.lblEnterPhoneNumber;
                      }
                      return null;
                    },
                  ),
                  16.height,
                  AppTextField(
                    controller: _addressController,
                    textFieldType: TextFieldType.MULTILINE,
                    minLines: 3,
                    maxLines: 5,
                    decoration: inputDecoration(context,
                        hint: languages.lblAddress, labelText: languages.lblAddress),
                    validator: (value) {
                      if (value.validate().isEmpty) {
                        return languages.lblAddress;
                      }
                      return null;
                    },
                  ),
                  16.height,
                  AppTextField(
                    controller: _noteController,
                    textFieldType: TextFieldType.MULTILINE,
                    minLines: 2,
                    maxLines: 4,
                    decoration: inputDecoration(context,
                        hint: languages.lblOrderNote,
                        labelText: languages.lblOrderNote),
                  ),
                  24.height,
                  Text(languages.lblPaymentMethod, style: boldTextStyle(size: 18)),
                  12.height,
                  Container(
                    width: double.infinity,
                    decoration: boxDecorationWithRoundedCorners(
                      borderRadius: radius(12),
                      backgroundColor:
                          appStore.isDarkMode ? cardDarkColor : context.cardColor,
                      border: Border.all(color: context.dividerColor),
                    ),
                    padding: EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                    child: Row(
                      children: [
                        Container(
                          padding: EdgeInsets.all(10),
                          decoration: boxDecorationWithRoundedCorners(
                            borderRadius: radius(12),
                            backgroundColor: primaryOpacity,
                          ),
                          child: Icon(Icons.local_shipping_outlined,
                              color: primaryColor),
                        ),
                        16.width,
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(languages.lblPaymentMethod,
                                style: secondaryTextStyle()),
                            4.height,
                            Text(languages.lblCashOnDelivery,
                                style: boldTextStyle()),
                          ],
                        )
                      ],
                    ),
                  ),
                  32.height,
                  AppButton(
                    text: languages.lblPlaceOrder,
                    color: primaryColor,
                    textStyle: boldTextStyle(color: white),
                    width: context.width(),
                    onTap: _placeOrder,
                  ),
                  40.height,
                ],
              ),
            ),
          ),
          if (_isPlacingOrder) Loader().center(),
        ],
      ),
    );
  }
}

class _CheckoutItemTile extends StatelessWidget {
  final CartItemModel item;

  const _CheckoutItemTile({required this.item});

  @override
  Widget build(BuildContext context) {
    final product = item.product;

    return Container(
      margin: EdgeInsets.only(bottom: 12),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          cachedImage(product?.productImage,
                  height: 60, width: 60, fit: BoxFit.cover)
              .cornerRadiusWithClipRRect(12),
          12.width,
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(product?.title.validate() ?? '-',
                    style: boldTextStyle(),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis),
                4.height,
                Text('${languages.lblQuantity}: ${item.quantity}',
                    style: secondaryTextStyle()),
                4.height,
                Text(
                  '${userStore.currencySymbol.validate()}${(item.totalPrice ?? 0).toStringAsFixed(2)}',
                  style: boldTextStyle(color: primaryColor),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
