from flask import Blueprint, redirect, flash, url_for
from models.student import delete_student

delete_bp = Blueprint("delete_student", __name__)

@delete_bp.route("/delete/<id>")
def index(id):
    delete_student(id)
    flash("Student successfully deleted!", "success")
    return redirect(url_for("read_student.students"))