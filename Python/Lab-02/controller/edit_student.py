from flask import Blueprint, render_template, request, url_for, redirect, flash
from controller.create_student import validate
from models.student import add_student, get_student_by_id, update_student

edit_bp = Blueprint("edit_student", __name__)

@edit_bp.route("/edit/<id>")
def index(id):
    data = get_student_by_id(id)
    return render_template("edit.html", fill=data)

@edit_bp.route("/editSubmit/<id>", methods=["POST"])
def edit_student_route(id):
    student_data = {
        "student_id": request.form.get("student_id"),
        "name": request.form.get("name"),
        "email": request.form.get("email"),
        "program": request.form.get("program"),
        "enrollment_date": request.form.get("enrollment_date")
    }
    errors = validate(student_data)
    if (errors):
        return render_template("edit.html", fill=student_data, errors=errors)
    update_student(id, student_data)
    flash("Student modified!", "success")
    return redirect(url_for("read_student.students"))