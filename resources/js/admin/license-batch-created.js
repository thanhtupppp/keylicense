window.adminLicenseBatchCreated = function () {
    return {
        toasts: [],
        counter: 0,
        push(message, type = "info") {
            const id = ++this.counter;
            const toast = {
                id,
                message,
                type,
                progress: 100,
                visible: true,
                className:
                    type === "success"
                        ? "border-emerald-500/30 bg-emerald-500/10 text-emerald-100"
                        : "border-rose-500/30 bg-rose-500/10 text-rose-100",
            };

            this.toasts.unshift(toast);

            const timer = setInterval(() => {
                toast.progress = Math.max(0, toast.progress - 2);

                if (toast.progress <= 0) {
                    clearInterval(timer);
                    this.remove(id);
                }
            }, 90);
        },
        remove(id) {
            const toast = this.toasts.find((item) => item.id === id);

            if (toast) {
                toast.visible = false;
            }

            setTimeout(() => {
                this.toasts = this.toasts.filter((item) => item.id !== id);
            }, 200);
        },
        copyKey(key) {
            return navigator.clipboard
                .writeText(key)
                .then(() => {
                    this.push("Đã sao chép key", "success");
                })
                .catch((err) => {
                    this.push(`Không thể sao chép: ${err}`, "error");
                });
        },
        copyAllKeys(keys) {
            return navigator.clipboard
                .writeText(keys.join("\n"))
                .then(() => {
                    this.push("Đã sao chép tất cả keys", "success");
                })
                .catch((err) => {
                    this.push(`Không thể sao chép: ${err}`, "error");
                });
        },
    };
};
