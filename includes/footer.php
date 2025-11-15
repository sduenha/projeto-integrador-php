        </main>

        <footer>
            <p>&copy; <?php echo date('Y'); ?> Sistema de Gestão de Aulas - Todos os direitos reservados</p>
        </footer>
    </div>
</body>
</html>
<?php
if (isset($conn)) {
    $conn->close();
}
?>