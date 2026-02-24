document.addEventListener("DOMContentLoaded", function () {

    const preview = document.getElementById("avatarPreview");
    const hairGrid = document.getElementById("hairGrid");

    const seed = "user";

    const hairStyles = [
        "short01","short02","long01","long02",
        "bun01","bun02","curly","straight"
    ];

    hairStyles.forEach(style => {

        const card = document.createElement("div");
        card.className = "card";

        const img = document.createElement("img");
        img.src = `https://api.dicebear.com/7.x/adventurer/png?seed=${seed}&hair=${style}`;

        card.appendChild(img);

        card.onclick = () => {
            preview.src = `https://api.dicebear.com/7.x/adventurer/png?seed=${seed}&hair=${style}`;
        };

        hairGrid.appendChild(card);
    });

    window.saveAvatar = function () {

        fetch("/avatar/save", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                avatar: preview.src
            })
        })
        .then(res => res.json())
        .then(() => alert("Saved!"));
    };

});
