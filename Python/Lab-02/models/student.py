from pymongo import MongoClient
from bson import ObjectId
from datetime import datetime

client = MongoClient("mongodb://localhost:27017/")
db = client["studentsdb"]
collection = db["students"]


def get_all_students():
    return list(collection.find())

def get_student_by_id(id):
    return collection.find_one(ObjectId(id))

def add_student(data):
    return collection.insert_one(data)

def delete_student(id):
    return collection.delete_one({"_id": ObjectId(id)})

def update_student(id, data):
    return collection.update_one({"_id": ObjectId(id)}, {"$set": data})