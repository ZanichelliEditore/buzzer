class ErrorHandler {
    constructor(response) {
        this.setResponse(response);
    }

    checkRes() {
        if (typeof this.response === "string") {
            this.message = this.response;
            return true;
        }

        if (this.response && this.response.data && this.response.data.message) {
            this.message = this.response.data.message;
            return true;
        }
        return false;
    }

    getMessage() {
        if (!this.checkRes()) {
            return "An unexpected error occurred, please retry";
        }
        if (this.response.data && this.response.data.errors) {
            let array = this.response.data.errors;

            let messages = "";
            for (var k in array) {
                messages = messages + array[k] + "\n";
            }
            return messages;
        }

        if (typeof this.message !== "array") {
            return this.message;
        }
        
        return "An unexpected error occurred, please retry";
    }

    setResponse(response) {
        this.response = response;
        this.message = "";
    }
}
export { ErrorHandler };
