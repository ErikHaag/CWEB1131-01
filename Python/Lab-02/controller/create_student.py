from flask import flash, Blueprint, render_template, request, url_for, redirect
from models.student import add_student, get_all_students
import re

create_bp = Blueprint("create_student", __name__)

def validate(data):
    errors = []
    if not data.get("student_id") or not re.match(r"^\d+$", data["student_id"]):
        errors.append("Student ID may only contain digits")
    if not data.get("name") or not re.match(r"^[A-Za-z ]+$", data["name"]):
        errors.append("Name must only contain letters and spaces")
    if not data.get("email") or not re.match(r"^[\w\-._]{5,}@\w{5,}\.\w{2,}$", data["email"]):
        errors.append("Email must contain")
    if not data.get("program") or not re.match(r"^[A-Za-z ]+$", data["program"]):
        errors.append("Program must only contain letters and spaces")
    if not data.get("enrollment_date") or not re.match(r"^\d{4}-(1[012]|0\d)-(3[01]|[012]\d)$", data["enrollment_date"]):
        errors.append("Enrollment date must be in YYYY-MM-DD format")
    return errors

@create_bp.route("/create", methods=["POST"])
def create_student_route():
    student_data = {
        "student_id": request.form.get("student_id"),
        "name": request.form.get("name"),
        "email": request.form.get("email"),
        "program": request.form.get("program"),
        "enrollment_date": request.form.get("enrollment_date")
    }
    
    errors = validate(student_data)
    if errors:
        return render_template("index.html", all_students=get_all_students(), errors=errors, fill=student_data, errorLoc="createStudentModal")
    
    add_student(student_data)
    flash("Student successfully added!", "success")
    return redirect(url_for("read_student.index"))