{{-- Tailwind CSS (CDN) + bảng màu "brand" dùng chung cho mọi trang.
     Trang đăng nhập và layout chính đều include file này, để class bg-brand-600... luôn có màu. --}}
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    brand: {
                        50: '#eff6ff', 100: '#dbeafe', 500: '#2563eb',
                        600: '#1d4ed8', 700: '#1e40af',
                    },
                },
            },
        },
    }
</script>
