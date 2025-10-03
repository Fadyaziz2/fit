
import 'dart:developer';
import 'dart:io';

import 'package:cloud_firestore/cloud_firestore.dart';
import 'package:firebase_storage/firebase_storage.dart';
import 'package:mighty_fitness/extensions/shared_pref.dart';
import 'package:mighty_fitness/models/login_response.dart';
import 'package:path/path.dart';

import '../../main.dart';
import '../Chat/model/chat_message_model.dart';
import '../Chat/model/contact_model.dart';
import '../utils/app_constants.dart';
import 'base_service.dart';

class ChatMessageService extends BaseService {
  FirebaseFirestore fireStore = FirebaseFirestore.instance;
  late CollectionReference userRef;
  FirebaseStorage _storage = FirebaseStorage.instance;

  ChatMessageService() {
    ref = fireStore.collection(MESSAGES_COLLECTION);
    userRef = fireStore.collection(USER_COLLECTION);
  }

  Query chatMessagesWithPagination(
      {String? currentUserId, required String receiverUserId}) {
    return ref!
        .doc(currentUserId)
        .collection(receiverUserId)
        .orderBy(KEY_FIREBASE_CREATED_AT, descending: true);
  }

  Future<DocumentReference> addMessage(ChatMessageModel data) async {
    var doc = await ref!
        .doc(data.senderId)
        .collection(data.receiverId!)
        .add(data.toJson());
    doc.update({KEY_ID: doc.id});
    return doc;
  }

  Future<void> addMessageToDb(DocumentReference senderDoc,
      ChatMessageModel data, UserModel sender, UserModel? user,
      {File? image}) async {
    String imageUrl = '';

    if (image != null) {
      String fileName = basename(image.path);
      Reference storageRef =
          _storage.ref().child("$CHAT_DATA_IMAGES/${sender.uid}/$fileName");

      UploadTask uploadTask = storageRef.putFile(image);

      await uploadTask.then((e) async {
        await e.ref.getDownloadURL().then((value) async {
          imageUrl = value;

          fileList.removeWhere((element) => element.id == senderDoc.id);
        }).catchError((e) {
          log(e);
        });
      }).catchError((e) {
        log(e);
      });
    }

    updateChatDocument(senderDoc, image: image, imageUrl: imageUrl);

    userRef.doc(data.senderId).update({KEY_LAST_MESSAGE_TIME: data.createdAt});
    addToContacts(senderId: data.senderId, receiverId: data.receiverId);

    DocumentReference receiverDoc = await ref!
        .doc(data.receiverId)
        .collection(data.senderId!)
        .add(data.toJson());

    updateChatDocument(receiverDoc, image: image, imageUrl: imageUrl);

    userRef
        .doc(data.receiverId)
        .update({KEY_LAST_MESSAGE_TIME: data.createdAt});
  }

  DocumentReference? updateChatDocument(DocumentReference data,
      {File? image, String? imageUrl}) {
    Map<String, dynamic> sendData = {KEY_ID: data.id};

    if (image != null) {
      sendData.putIfAbsent(KEY_PHOTO_URL, () => imageUrl);
    }
    log(sendData.toString());
    data.update(sendData);

    log("Data $sendData");
    return null;
  }

  DocumentReference getContactsDocument({String? of, String? forContact}) {
    return userRef.doc(of).collection(CONTACT_COLLECTION).doc(forContact);
  }

  addToContacts({String? senderId, String? receiverId}) async {
    Timestamp currentTime = Timestamp.now();

    await addToSenderContacts(senderId, receiverId, currentTime);
    await addToReceiverContacts(senderId, receiverId, currentTime);
  }

  Future<void> addToSenderContacts(
      String? senderId, String? receiverId, currentTime) async {
    DocumentSnapshot senderSnapshot =
        await getContactsDocument(of: senderId, forContact: receiverId).get();

    if (!senderSnapshot.exists) {
      //does not exists
      ContactModel receiverContact = ContactModel(
        uid: receiverId,
        addedOn: currentTime,
      );

      await getContactsDocument(of: senderId, forContact: receiverId)
          .set(receiverContact.toJson());
    }
  }

  Future<void> addToReceiverContacts(
    String? senderId,
    String? receiverId,
    currentTime,
  ) async {
    DocumentSnapshot receiverSnapshot =
        await getContactsDocument(of: receiverId, forContact: senderId).get();

    if (!receiverSnapshot.exists) {
      //does not exists
      ContactModel senderContact = ContactModel(
        uid: senderId,
        addedOn: currentTime,
      );
      await getContactsDocument(of: receiverId, forContact: senderId)
          .set(senderContact.toJson());
    }
  }

  //Fetch User List

  Stream<QuerySnapshot> fetchContacts({String? userId}) {
    return userRef.doc(userId).collection(CONTACT_COLLECTION).snapshots();
  }

  Stream<List<UserModel>> getUserDetailsById({String? id, String? searchText}) {
    return userRef
        .where(KEY_UID, isEqualTo: id)
        .where(KEY_CASE_SEARCH,
            arrayContains: searchText?.isEmpty??false
                ? null
                : searchText!.toLowerCase())
        .snapshots()
        .map((event) => event.docs
            .map((e) => UserModel.fromJson(e.data() as Map<String, dynamic>))
            .toList());
  }

  Stream<QuerySnapshot> fetchLastMessageBetween(
      {required String senderId, required String receiverId}) {
    return ref!
        .doc(senderId.toString())
        .collection(receiverId.toString())
        .orderBy(KEY_FIREBASE_CREATED_AT, descending: false)
        .snapshots();
  }

  Future<void> clearAllMessages(
      {String? senderId, required String receiverId}) async {
    final WriteBatch _batch = fireStore.batch();

    ref!.doc(senderId).collection(receiverId).get().then((value) {
      value.docs.forEach((document) {
        _batch.delete(document.reference);
      });

      return _batch.commit();
    }).catchError((error, stackTrace) {});
  }

  Future<void> deleteChat(
      {String? senderId, required String receiverId}) async {
    ref!.doc(senderId).collection(receiverId).doc().delete();
    userRef
        .doc(senderId)
        .collection(CONTACT_COLLECTION)
        .doc(receiverId)
        .delete();
  }

  Future<void> deleteSingleMessage(
      {String? senderId,
      required String receiverId,
      String? documentId}) async {
    try {
      ref!.doc(senderId).collection(receiverId).doc(documentId).delete();
    } on Exception catch (e) {
      log(e.toString());
      // throw language.somethingWentWrong;
    }
  }

  Future<void> setUnReadStatusToTrue(
      {required String senderId,
      required String receiverId,
      String? documentId}) async {
    ref!
        .doc(senderId)
        .collection(receiverId)
        .where(KEY_IS_MESSAGE_READ, isEqualTo: false)
        .get()
        .then((value) {
      value.docs.forEach((element) {
        element.reference.update({
          KEY_IS_MESSAGE_READ: true,
        });
      });
    });

    ref!
        .doc(receiverId)
        .collection(senderId)
        .where(KEY_IS_MESSAGE_READ, isEqualTo: false)
        .get()
        .then((value) {
      value.docs.forEach((element) {
        element.reference.update({
          KEY_IS_MESSAGE_READ: true,
        });
      });
    });
  }

  Stream<int> getUnReadCount(
      {String? senderId, required String receiverId, String? documentId}) {
    return ref!
        .doc(senderId.toString())
        .collection(receiverId)
        .where(KEY_IS_MESSAGE_READ, isEqualTo: false)
        .where(KEY_RECEIVER_ID, isEqualTo: senderId)
        .snapshots()
        .map(
          (event) => event.docs.length,
        )
        .handleError((e) {
      return e;
    });
  }

  int fetchForMessageCount(currentUserId) {
    List<ContactModel> contactList = [];
    userStore.chatNotificationCount = 0;
    Query query1 =
        userRef.doc(currentUserId.toString()).collection(CONTACT_COLLECTION);
    query1.get().then((value) {
      value.docs.forEach((element) {
        ContactModel contactData =
            ContactModel.fromJson(element.data() as Map<String, dynamic>);
        contactList.add(contactData);
      });
      contactList.forEach((e2) {
        Query query = ref!
            .doc(e2.uid.toString())
            .collection(currentUserId)
            .orderBy("createdAt", descending: true);
        query.get().then((value) {
          if (value.docs.first.data() != null) {
            ChatMessageModel data = ChatMessageModel.fromJson(
                value.docs.first.data() as Map<String, dynamic>);
            if (data.receiverId == getStringAsync(UID) && !(data.isMessageRead ?? false)) {
              userStore.chatNotificationCount =
                  userStore.chatNotificationCount + 1;
            }
          }
        }).catchError((e) {
          log(e.toString());
        });
      });
    }).catchError((e) {
      log(e);
    });

    return userStore.chatNotificationCount;
  }

  Future<UserModel> getUserPlayerId({String? uid}) {
    print("data->>>" + uid.toString());
    return userRef.where(KEY_UID, isEqualTo: uid).limit(1).get().then((value) {
      if (value.docs.length == 1) {
        return UserModel.fromJson(
            value.docs.first.data() as Map<String, dynamic>);
      } else {
        throw 'User Not found';
      }
    });
  }
}

