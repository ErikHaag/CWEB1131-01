from flask import Blueprint, render_template, request
from models.student import get_all_students

read_bp = Blueprint('read_student', __name__)

@read_bp.route("/")
def index():
    return render_template("index.html")

@read_bp.route("/students")
def students():
    return render_template("table.html")

# I figured out how to do an API, I forgot the methods argument...
# @read_bp.route("/api", methods=["POST"])
# def api():
#     return request.get_json()