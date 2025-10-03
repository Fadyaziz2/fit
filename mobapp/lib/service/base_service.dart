import 'package:cloud_firestore/cloud_firestore.dart';
import 'package:mighty_fitness/models/login_response.dart';
import 'package:mighty_fitness/utils/app_constants.dart';


abstract class BaseService {
  CollectionReference? ref;

  BaseService({this.ref});

  Future<DocumentReference> addDocument(Map data) async {
    var doc = await ref!.add(data);
    doc.update({KEY_UID: doc.id});
    return doc;
  }

  Future<DocumentReference> addDocumentWithCustomId(
      String id, Map<String, dynamic> data) async {
    var doc = ref!.doc(id);

    return await doc.set(data).then((value) {

      return doc;
    }).catchError((e) {
      print(e);
      throw e;
    });
  }

  Future<void> updateDocument(Map<String, dynamic> data, String? id) async {
    await ref!.doc(id).update(data);
  }

  Future<void> removeDocument(String id) => ref!.doc(id).delete();


  Future<Iterable> getList() async {
    var res = await ref!.get();
    Iterable it = res.docs;
    return it;
  }

  Stream<List<UserModel>> users({String? searchText}) {
    return ref!
        .where(KEY_CASE_SEARCH,
            arrayContains: searchText?.isEmpty??false
                ? null
                : searchText!.toLowerCase())
        .snapshots()
        .map((x) {
      return x.docs.map((y) {
        return UserModel.fromJson(y.data() as Map<String, dynamic>);
      }).toList();
    });
  }
}
