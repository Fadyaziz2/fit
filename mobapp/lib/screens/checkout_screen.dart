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
import '../models/discount_response.dart';
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
  late TextEditingController _discountController;

  bool _isPlacingOrder = false;
  bool _isApplyingDiscount = false;
  DiscountSummary? _discountSummary;

  @override
  void initState() {
    super.initState();
    _nameController = TextEditingController(text: _resolveDefaultName());
    _phoneController = TextEditingController(text: userStore.phoneNo.validate());
    _addressController = TextEditingController();
    _noteController = TextEditingController();
    _discountController = TextEditingController();
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
    _discountController.dispose();
    super.dispose();
  }

  Future<void> _placeOrder() async {
    if (!(_formKey.currentState?.validate() ?? false)) {
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
      if (_discountSummary?.code.validate().isNotEmpty ?? false)
        'discount_code': _discountSummary!.code,
    };

    await checkoutApi(request).then((CheckoutResponse value) {
      toast(value.message ?? languages.lblOrderPlacedSuccess);
      cartCountNotifier.value = 0;
      if (mounted) {
        Navigator.pop(context, true);
      }
      _discountSummary = null;
      _discountController.clear();
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

  Future<void> _applyDiscountCode() async {
    final code = _discountController.text.trim();

    if (code.isEmpty) {
      toast(languages.lblEnterDiscountCode);
      return;
    }

    FocusScope.of(context).unfocus();

    setState(() {
      _isApplyingDiscount = true;
    });

    await applyDiscountCodeApi({'code': code}).then((value) {
      setState(() {
        _discountSummary = value.data;
      });
      toast(value.message ?? languages.lblDiscountApplied);
    }).catchError((e) {
      toast(e.toString().validate(value: languages.lblInvalidDiscountCode));
    }).whenComplete(() {
      if (mounted) {
        setState(() {
          _isApplyingDiscount = false;
        });
      }
    });
  }

  void _removeDiscountCode() {
    setState(() {
      _discountSummary = null;
      _discountController.clear();
    });
    toast(languages.lblDiscountRemoved);
  }

  @override
  Widget build(BuildContext context) {
    final cartItems = widget.cartResponse.data ?? [];
    final summary = widget.cartResponse.summary;
    double resolveAmount(num? value) => value != null ? value.toDouble() : 0;

    final subtotalAmount = resolveAmount(
        _discountSummary?.subtotalAmount ?? summary?.subtotalAmount ?? summary?.totalAmount);
    final discountAmount = resolveAmount(
        _discountSummary?.discountAmount ?? summary?.discountAmount ?? 0);
    final payableAmount = resolveAmount(
        _discountSummary?.payableAmount ?? summary?.payableAmount ?? summary?.totalAmount);
    final currency = userStore.currencySymbol.validate();

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
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        ...cartItems.map((item) => _CheckoutItemTile(item: item))
                            .toList(),
                        Divider(height: 24),
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text(languages.lblSubtotal,
                                style: secondaryTextStyle()),
                            Text(
                              '$currency${subtotalAmount.toStringAsFixed(2)}',
                              style: boldTextStyle(),
                            ),
                          ],
                        ),
                        6.height,
                        if (discountAmount > 0)
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Text(languages.lblDiscount,
                                  style: secondaryTextStyle(color: Colors.green)),
                              Text(
                                '-$currency${discountAmount.toStringAsFixed(2)}',
                                style: boldTextStyle(color: Colors.green),
                              ),
                            ],
                          ),
                        if (discountAmount > 0) 6.height,
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text(languages.lblPayableAmount,
                                style: boldTextStyle(size: 16)),
                            Text(
                              '$currency${payableAmount.toStringAsFixed(2)}',
                              style: boldTextStyle(size: 18, color: primaryColor),
                            ),
                          ],
                        ),
                        if (_discountSummary?.code.validate().isNotEmpty ?? false) ...[
                          8.height,
                          Text(
                            '${languages.lblDiscountCode}: ${_discountSummary!.code.validate()}',
                            style: secondaryTextStyle(),
                          ),
                        ]
                      ],
                    ),
                  ),
                  24.height,
                  Text(languages.lblDiscountCode, style: boldTextStyle(size: 18)),
                  12.height,
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Expanded(
                        child: AppTextField(
                          controller: _discountController,
                          textFieldType: TextFieldType.OTHER,
                          decoration: defaultInputDecoration(context,
                              hint: languages.lblEnterDiscountCode,
                              label: languages.lblEnterDiscountCode),
                        ),
                      ),
                      12.width,
                      AppButton(
                        text: _discountSummary == null
                            ? languages.lblApplyCode
                            : languages.lblRemoveCode,
                        width: 130,
                        color: _discountSummary == null ? primaryColor : Colors.redAccent,
                        textStyle: boldTextStyle(color: white),
                        enabled: _discountSummary == null
                            ? !_isApplyingDiscount
                            : true,
                        onTap: _discountSummary == null
                            ? (_isApplyingDiscount ? null : _applyDiscountCode)
                            : _removeDiscountCode,
                      ),
                    ],
                  ),
                  24.height,
                  Text(languages.lblShippingAddress, style: boldTextStyle(size: 18)),
                  12.height,
                  AppTextField(
                    controller: _nameController,
                    textFieldType: TextFieldType.NAME,
                    decoration: defaultInputDecoration(context,
                        hint: languages.lblFullName, label: languages.lblFullName),
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
                    decoration: defaultInputDecoration(context,
                        hint: languages.lblPhoneNumber, label: languages.lblPhoneNumber),
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
                    decoration: defaultInputDecoration(context,
                        hint: languages.lblAddress, label: languages.lblAddress),
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
                    decoration: defaultInputDecoration(context,
                        hint: languages.lblOrderNote,
                        label: languages.lblOrderNote),
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
