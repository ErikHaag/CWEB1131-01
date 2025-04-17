from flask import Flask
from controller.create_student import create_bp
from controller.read_student import read_bp
from controller.edit_student import edit_bp

app = Flask(__name__)

app.register_blueprint(create_bp)
app.register_blueprint(read_bp)
app.register_blueprint(edit_bp)

app.secret_key = "your-secret-key"

if __name__ == "__main__":
    app.run(debug=True)