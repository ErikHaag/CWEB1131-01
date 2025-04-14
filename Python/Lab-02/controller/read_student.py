from flask import Blueprint, render_template
from models.student import get_all_students

read_bp = Blueprint('read_student', __name__)

@read_bp.route("/")
def index():
    return render_template("index.html", all_students=get_all_students())